import { StrictMode, useState, useEffect } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.jsx'
import PostsGallery from './PostsGallery.jsx'
import LocalDraft from './LocalDraft.jsx'

const MW = {
  purple: "#5B4FBE", purpleBg: "#EEEDFE", softLav: "#C8C4E8",
  border: "#E5E3F5", white: "#FFFFFF", muted: "#999999", titleText: "#444444",
  bodyText: "#333333", callout: "#E85D3A", rowAlt: "#F5F7FA",
};

// ─── Login page ───────────────────────────────────────────────────────────────
function LoginPage() {
  const [error,   setError]   = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const e = params.get("auth_error");
    if (e === "domain") setError("Only @masterworks.com accounts are allowed.");
    else if (e)         setError("Authentication failed. Please try again.");
    if (e) window.history.replaceState({}, "", "/");
  }, []);

  async function handleSignIn() {
    setLoading(true);
    setError("");
    try {
      const res = await fetch("/auth/url");
      const { url, error: err } = await res.json();
      if (err || !url) throw new Error(err || "No auth URL returned");
      window.location.href = url;
    } catch (e) {
      setError("Could not reach the auth server. Is it running?");
      setLoading(false);
    }
  }

  return (
    <div style={{
      fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
      background: "#F8F8FB", minHeight: "100vh",
      display: "flex", alignItems: "center", justifyContent: "center",
      padding: "16px",
    }}>
      <div style={{
        width: "100%", maxWidth: 380,
        background: MW.white, border: `0.5px solid ${MW.border}`,
        borderRadius: 14, padding: "36px 32px",
        textAlign: "center",
      }}>
        {/* Brand header */}
        <div style={{ marginBottom: 28 }}>
          <div style={{
            fontSize: 10, fontWeight: 500, letterSpacing: "0.12em",
            textTransform: "uppercase", color: MW.purple, marginBottom: 10,
          }}>
            Masterworks · Art Market Intelligence
          </div>
          <h1 style={{
            fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif",
            fontSize: 22, fontWeight: 900, margin: "0 0 4px",
            color: MW.titleText,
          }}>
            Social Post <span style={{ color: MW.purple }}>Generator</span>
          </h1>
          <div style={{ width: 32, height: 3, background: MW.purple, margin: "10px auto 0" }} />
        </div>

        <p style={{ fontSize: 13, color: MW.muted, margin: "0 0 24px", lineHeight: 1.6 }}>
          Sign in with your Masterworks Google account to continue.
        </p>

        {error && (
          <div style={{
            fontSize: 12, color: MW.callout,
            background: "#FFF5F5", border: `0.5px solid #F9C9C0`,
            borderRadius: 4, padding: "8px 12px", marginBottom: 16,
          }}>
            {error}
          </div>
        )}

        <button
          onClick={handleSignIn}
          disabled={loading}
          style={{
            width: "100%",
            display: "flex", alignItems: "center", justifyContent: "center", gap: 10,
            padding: "10px 16px",
            background: MW.white, border: `1px solid ${MW.border}`,
            borderRadius: 4, cursor: loading ? "default" : "pointer",
            fontSize: 13, fontWeight: 500,
            fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
            color: loading ? MW.muted : MW.bodyText, letterSpacing: "0.01em",
            opacity: loading ? 0.7 : 1,
          }}
        >
          {/* Google "G" logo */}
          <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            <path fill="none" d="M0 0h48v48H0z"/>
          </svg>
          {loading ? "Connecting…" : "Sign in with Google"}
        </button>
      </div>
    </div>
  );
}

// ─── Nav + page switcher ──────────────────────────────────────────────────────
const navBtn = (active) => ({
  border: `1px solid ${active ? MW.purple : MW.border}`,
  borderRadius: 4,
  background: active ? MW.purpleBg : MW.white,
  cursor: "pointer",
  fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
  fontSize: 12,
  color: active ? MW.purple : MW.muted,
  padding: "6px 16px",
});

const IS_LOCAL = typeof window !== "undefined" && window.location.hostname === "localhost";

function Root() {
  const [authed, setAuthed] = useState(null);   // null = loading
  const [user,   setUser]   = useState(null);
  const [page,   setPage]   = useState("generator");

  useEffect(() => {
    fetch("/auth/me")
      .then(r => r.json())
      .then(data => {
        setAuthed(data.authed);
        if (data.authed) setUser(data);
      })
      .catch(() => setAuthed(false));
  }, []);

  // Loading splash
  if (authed === null) {
    return (
      <div style={{
        minHeight: "100vh", display: "flex", alignItems: "center", justifyContent: "center",
        fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
        color: MW.muted, fontSize: 13, background: "#F8F8FB",
      }}>
        Loading…
      </div>
    );
  }

  if (!authed) return <LoginPage />;

  return (
    <>
      {/* Sticky top nav */}
      <div style={{
        position: "sticky", top: 0, zIndex: 100,
        background: MW.white, borderBottom: `0.5px solid ${MW.border}`,
        padding: "8px 24px",
        display: "flex", alignItems: "center", justifyContent: "space-between",
      }}>
        <span style={{
          fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif",
          fontSize: 14, fontWeight: 700, color: MW.titleText, letterSpacing: "0.01em",
        }}>
          MW <span style={{ color: MW.purple }}>Post Generator</span>
        </span>

        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <button onClick={() => setPage("generator")} style={navBtn(page === "generator")}>
            Generator
          </button>
          <button onClick={() => setPage("gallery")} style={navBtn(page === "gallery")}>
            Post Gallery
          </button>
          {IS_LOCAL && (
            <button onClick={() => setPage("local")} style={navBtn(page === "local")}>
              Local Draft
            </button>
          )}

          {/* User + logout */}
          {user?.picture && (
            <img
              src={user.picture}
              alt={user.name || user.email}
              referrerPolicy="no-referrer"
              style={{ width: 26, height: 26, borderRadius: "50%", border: `1px solid ${MW.border}`, marginLeft: 4 }}
            />
          )}
          <a
            href="/auth/logout"
            style={{
              fontSize: 11, color: MW.muted, textDecoration: "none",
              fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
              letterSpacing: "0.04em",
            }}
          >
            Sign out
          </a>
        </div>
      </div>

      {page === "generator" ? <App /> : page === "gallery" ? <PostsGallery /> : <LocalDraft />}
    </>
  );
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Root />
  </StrictMode>,
)
