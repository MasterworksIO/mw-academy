import 'dotenv/config';   // loads .env in dev; no-op in production (Railway injects vars)
import express from 'express';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { google } from 'googleapis';
import { Readable } from 'stream';
import crypto from 'crypto';
import { readFile } from 'fs/promises';

const __dirname = dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 3000;

const LAMBDA_URL    = 'https://7fwhq6pkexd2p6px4q7fstuvd40hsjaq.lambda-url.us-east-2.on.aws';
const ALLOWED_DOMAIN = 'masterworks.com';
const SESSION_TTL   = 8 * 60 * 60 * 1000;   // 8 hours

// ── Session store ──────────────────────────────────────────────────────────────
const sessions = new Map();

setInterval(() => {
  const cutoff = Date.now() - SESSION_TTL;
  for (const [id, s] of sessions) {
    if (s.createdAt < cutoff) sessions.delete(id);
  }
}, 60 * 60 * 1000);

function parseCookies(req) {
  const out = {};
  (req.headers.cookie || '').split(';').forEach(part => {
    const [k, ...v] = part.trim().split('=');
    if (k) out[k.trim()] = decodeURIComponent(v.join('='));
  });
  return out;
}

function getSession(req) {
  const { mw_session: id } = parseCookies(req);
  if (!id) return null;
  const s = sessions.get(id);
  if (!s || Date.now() - s.createdAt > SESSION_TTL) { sessions.delete(id); return null; }
  return s;
}

function requireAuth(req, res, next) {
  if (!getSession(req)) return res.status(401).json({ error: 'Unauthorized' });
  next();
}

// ── Google OAuth2 ──────────────────────────────────────────────────────────────
const oauth2Client = new google.auth.OAuth2(
  process.env.GOOGLE_CLIENT_ID,
  process.env.GOOGLE_CLIENT_SECRET,
  process.env.GOOGLE_REDIRECT_URI || 'http://localhost:5173/auth/callback'
);

// Returns the Google OAuth URL as JSON — client navigates directly (avoids proxy redirect issues)
app.get('/auth/url', (_req, res) => {
  try {
    const url = oauth2Client.generateAuthUrl({
      access_type: 'online',
      scope: ['openid', 'email', 'profile'],
      hd: ALLOWED_DOMAIN,
      prompt: 'select_account',
    });
    res.json({ url });
  } catch (e) {
    console.error('generateAuthUrl error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

app.get('/auth/callback', async (req, res) => {
  const { code, error } = req.query;
  if (error || !code) return res.redirect('/?auth_error=access_denied');
  try {
    const { tokens } = await oauth2Client.getToken(code);
    const ticket = await oauth2Client.verifyIdToken({
      idToken: tokens.id_token,
      audience: process.env.GOOGLE_CLIENT_ID,
    });
    const { email, name, picture } = ticket.getPayload();
    if (!email?.toLowerCase().endsWith(`@${ALLOWED_DOMAIN}`)) {
      return res.redirect('/?auth_error=domain');
    }
    const sessionId = crypto.randomBytes(32).toString('hex');
    sessions.set(sessionId, { email, name, picture, createdAt: Date.now() });
    res.cookie('mw_session', sessionId, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      maxAge: SESSION_TTL,
    });
    res.redirect('/');
  } catch (e) {
    console.error('OAuth callback error:', e.message);
    res.redirect('/?auth_error=server');
  }
});

app.get('/auth/me', (req, res) => {
  const s = getSession(req);
  if (!s) return res.status(401).json({ authed: false });
  res.json({ authed: true, email: s.email, name: s.name, picture: s.picture });
});

app.get('/auth/logout', (req, res) => {
  const { mw_session: id } = parseCookies(req);
  if (id) sessions.delete(id);
  res.clearCookie('mw_session');
  res.redirect('/');
});

// ── Local draft (Claude Code workflow) ────────────────────────────────────────
app.get('/local-draft', requireAuth, async (_req, res) => {
  try {
    const content = await readFile(join(__dirname, 'local_draft.json'), 'utf8');
    res.json(JSON.parse(content));
  } catch (e) {
    if (e.code === 'ENOENT') return res.status(404).json({ error: 'No local_draft.json found in the project root.' });
    res.status(500).json({ error: e.message });
  }
});

// ── Google Drive helpers ───────────────────────────────────────────────────────
function makeDriveClient(scopes) {
  const credentials = JSON.parse(process.env.GOOGLE_SERVICE_ACCOUNT_JSON);
  const auth = new google.auth.GoogleAuth({ credentials, scopes });
  return google.drive({ version: 'v3', auth });
}

async function uploadToDrive(filename, jsonContent, folderId) {
  const drive = makeDriveClient(['https://www.googleapis.com/auth/drive.file']);
  const { data } = await drive.files.create({
    requestBody: { name: filename, parents: [folderId], mimeType: 'application/json' },
    media: { mimeType: 'application/json', body: Readable.from([jsonContent]) },
    fields: 'id,webViewLink',
    supportsAllDrives: true
  });
  return data;
}

// ── Save-to-Drive endpoint ─────────────────────────────────────────────────────
app.use(express.json({ limit: '10mb' }));

app.post('/save', requireAuth, async (req, res) => {
  const { payload, status } = req.body;
  if (!payload || !status) {
    return res.status(400).json({ error: "Missing 'payload' or 'status'" });
  }

  const folderId = status === 'approved'
    ? process.env.DRIVE_FOLDER_APPROVED
    : process.env.DRIVE_FOLDER_NEEDS_REVIEW;

  if (!folderId) {
    return res.status(500).json({ error: `Env var not set for status: ${status}` });
  }

  const ts       = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
  const filename = `post_${ts}.json`;

  try {
    const file = await uploadToDrive(filename, JSON.stringify(payload, null, 2), folderId);
    res.json({ success: true, fileId: file.id, url: file.webViewLink });
  } catch (e) {
    console.error('Drive upload error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

// ── Update post content in Drive ──────────────────────────────────────────────
app.patch('/posts/:fileId', requireAuth, async (req, res) => {
  const { fileId } = req.params;
  const { content } = req.body || {};
  if (!content) return res.status(400).json({ error: 'Missing content' });
  try {
    const drive = makeDriveClient(['https://www.googleapis.com/auth/drive.file']);
    await drive.files.update({
      fileId,
      media: { mimeType: 'application/json', body: Readable.from([JSON.stringify(content, null, 2)]) },
      supportsAllDrives: true,
    });
    res.json({ success: true });
  } catch (e) {
    console.error('Drive patch error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

// ── Move post from Needs Review → Approved ────────────────────────────────────
app.post('/posts/:fileId/approve', requireAuth, async (req, res) => {
  const { fileId } = req.params;
  const { content } = req.body || {};
  if (!process.env.DRIVE_FOLDER_APPROVED || !process.env.DRIVE_FOLDER_NEEDS_REVIEW) {
    return res.status(500).json({ error: 'Drive folder env vars not set' });
  }
  try {
    const drive = makeDriveClient(['https://www.googleapis.com/auth/drive.file']);
    // Update content first (if provided), then move
    if (content) {
      await drive.files.update({
        fileId,
        media: { mimeType: 'application/json', body: Readable.from([JSON.stringify(content, null, 2)]) },
        supportsAllDrives: true,
      });
    }
    await drive.files.update({
      fileId,
      addParents: process.env.DRIVE_FOLDER_APPROVED,
      removeParents: process.env.DRIVE_FOLDER_NEEDS_REVIEW,
      fields: 'id',
      supportsAllDrives: true,
    });
    res.json({ success: true });
  } catch (e) {
    console.error('Drive approve error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

// ── List & read posts from a Drive folder ─────────────────────────────────────
app.get('/posts', requireAuth, async (req, res) => {
  const { folder } = req.query;
  const folderId = folder === 'approved'
    ? process.env.DRIVE_FOLDER_APPROVED
    : process.env.DRIVE_FOLDER_NEEDS_REVIEW;

  if (!folderId) {
    return res.status(400).json({ error: `Unknown folder: ${folder}` });
  }

  try {
    const drive = makeDriveClient(['https://www.googleapis.com/auth/drive.readonly']);

    const listRes = await drive.files.list({
      q: `'${folderId}' in parents and mimeType = 'application/json' and trashed = false`,
      fields: 'files(id, name, createdTime, webViewLink)',
      orderBy: 'createdTime desc',
      pageSize: 50,
      supportsAllDrives: true,
      includeItemsFromAllDrives: true,
    });

    const files = listRes.data.files || [];

    const posts = await Promise.all(files.map(async (file) => {
      try {
        const contentRes = await drive.files.get(
          { fileId: file.id, alt: 'media', supportsAllDrives: true },
          { responseType: 'json' }
        );
        const content = contentRes.data ?? {};
        return { fileId: file.id, fileName: file.name, createdTime: file.createdTime, url: file.webViewLink, ...content };
      } catch (e) {
        return { fileId: file.id, fileName: file.name, createdTime: file.createdTime, url: file.webViewLink, error: e.message };
      }
    }));

    res.json({ posts });
  } catch (e) {
    console.error('Drive list error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

// ── Forward /api/* → Lambda ────────────────────────────────────────────────────
app.use('/api', requireAuth, async (req, res) => {
  const lambdaUrl = `${LAMBDA_URL}${req.path === '/' ? '/' : req.path}`;
  console.log(`[lambda] → ${req.method} ${lambdaUrl}`);
  try {
    const fetchRes = await fetch(lambdaUrl, {
      method:  req.method,
      headers: {
        'Content-Type': 'application/json',
        'x-internal-secret': process.env.LAMBDA_SHARED_SECRET,
      },
      body:    req.method !== 'GET' && req.method !== 'HEAD' ? JSON.stringify(req.body) : undefined,
      signal:  AbortSignal.timeout(120_000),
    });
    console.log(`[lambda] ← ${fetchRes.status}`);
    const data = await fetchRes.json();
    res.status(fetchRes.status).json(data);
  } catch (e) {
    console.error(`[lambda] error: ${e.message}`);
    res.status(502).json({ error: e.message });
  }
});

// Serve the Vite build output
app.use(express.static(join(__dirname, 'dist')));

// SPA fallback — send index.html for any unmatched route
app.get('*', (_req, res) => {
  res.sendFile(join(__dirname, 'dist', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
