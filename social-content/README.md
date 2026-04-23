# MW Post Generator

A web app for Masterworks' Art Market Intelligence team to generate LinkedIn and Instagram posts from live auction data. It queries Amazon Redshift via an AI-powered Lambda backend, surfaces analytics and charts, then drafts platform-specific captions that can be saved to Google Drive for review and publication.

---

## How it works

1. Sign in with your `@masterworks.com` Google account
2. Query auction data using a preset or a natural language request
3. Review charts and key stats (sell-through %, hammer price, premiums, etc.)
4. Choose a platform (Instagram / LinkedIn) and tone, then generate a post
5. Edit the draft and save it to Google Drive as **Approved** or **Needs Review**
6. Browse saved posts in the gallery, edit them, and export as a ZIP

---

## Tech stack

| Layer | Tech |
|---|---|
| Frontend | React 19, Vite 8, Recharts |
| Backend | Node.js, Express 4 |
| Auth | Google OAuth2 (restricted to @masterworks.com) |
| Storage | Google Drive (via service account) |
| Data | AWS Lambda → Amazon Redshift |
| Deployment | Railway |

---

## Getting started

### Prerequisites

- Node.js 18+
- A `.env` file (see below)

### Install and run

```bash
npm install
npm run dev
```

This starts:
- **Vite dev server** at `http://localhost:5173` (hot reload)
- **Express server** at `http://localhost:3000` (auth, Drive, Lambda proxy)

Vite proxies `/auth`, `/api`, `/save`, `/posts`, and `/local-draft` to the Express server automatically.

### Build for production

```bash
npm run build   # outputs to dist/
npm start       # serves on $PORT (default 3000)
```

---

## Environment variables

Create a `.env` file in the project root with the following:

```env
# Google OAuth2
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:5173/auth/callback

# Google Drive (service account JSON, stringified)
GOOGLE_SERVICE_ACCOUNT_JSON=
DRIVE_FOLDER_APPROVED=
DRIVE_FOLDER_NEEDS_REVIEW=

# Lambda backend
LAMBDA_URL=
LAMBDA_SHARED_SECRET=

# Redshift (used by the Lambda function, not the Node server directly)
REDSHIFT_HOST=
REDSHIFT_PORT=5439
REDSHIFT_USER=
REDSHIFT_PASSWORD=
REDSHIFT_DB=
```

**Google Drive setup:** Create two folders in Drive, share both with the service account email, and paste their IDs into `DRIVE_FOLDER_APPROVED` and `DRIVE_FOLDER_NEEDS_REVIEW`.

---

## Project structure

```
mw-post-generator/
├── src/
│   ├── main.jsx            # Root with auth check and page routing
│   ├── App.jsx             # Post generator (query → charts → generate → save)
│   ├── PostsGallery.jsx    # Gallery: browse, edit, approve, download posts
│   └── LocalDraft.jsx      # Load drafts from local_draft.json for quick iteration
├── server.js               # Express server (auth, Drive API, Lambda proxy)
├── vite.config.js
├── railway.toml            # Railway deployment config
└── local_draft.json        # Local draft file (not committed)
```

---

## API reference

### Auth

| Method | Path | Description |
|---|---|---|
| `GET` | `/auth/url` | Returns the Google OAuth consent URL |
| `GET` | `/auth/callback` | OAuth2 callback |
| `GET` | `/auth/me` | Current user or 401 |
| `GET` | `/auth/logout` | Clear session |

### Posts

| Method | Path | Description |
|---|---|---|
| `POST` | `/save` | Save a post to Drive |
| `GET` | `/posts?folder=approved\|needs_review` | List posts from a folder |
| `PATCH` | `/posts/:fileId` | Update post content |
| `POST` | `/posts/:fileId/approve` | Move post to Approved folder |

### Data

| Method | Path | Description |
|---|---|---|
| `*` | `/api/*` | Proxied to the Lambda backend |
| `GET` | `/local-draft` | Read `local_draft.json` |

---

## Deployment

The app deploys to [Railway](https://railway.app) via `railway.toml`:

- **Build:** `npm install && npm run build`
- **Start:** `node server.js`

Set all environment variables in the Railway project dashboard before deploying.

---

## Notes

- Sessions are in-memory with an 8-hour TTL. For high-availability deployments, swap in a persistent session store.
- The Lambda function must accept `POST` requests with an `x-shared-secret` header matching `LAMBDA_SHARED_SECRET`.
- `local_draft.json` is excluded from git and used only for the Local Draft development page.
