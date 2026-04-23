import { useState, useEffect } from "react";
import JSZip from "jszip";

// ─── Masterworks Brand Tokens ─────────────────────────────────────────────────
const MW = {
  purple:   "#5B4FBE", lavender: "#8B83D0", softLav:  "#C8C4E8",
  purpleBg: "#EEEDFE", titleText:"#444444", bodyText: "#333333",
  subText:  "#555555", muted:    "#999999", border:   "#E5E3F5",
  rowAlt:   "#F5F7FA", green:    "#2D7A16", callout:  "#E85D3A",
  white:    "#FFFFFF",
};

const eyebrow   = { fontSize:10, fontWeight:500, letterSpacing:"0.1em", textTransform:"uppercase", color:MW.purple, marginBottom:10, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif" };
const btnBase   = { border:`1px solid ${MW.border}`, borderRadius:4, background:MW.white, cursor:"pointer", fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif", fontSize:12, color:MW.muted, padding:"6px 14px" };
const btnActive = { ...btnBase, border:`1px solid ${MW.purple}`, background:MW.purpleBg, color:MW.purple };
const btnPrimary  = { padding:"8px 22px", background:MW.purple, color:MW.white, border:"none", borderRadius:4, cursor:"pointer", fontSize:13, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif", fontWeight:500 };
const btnDisabled = { ...btnPrimary, background:MW.rowAlt, color:MW.muted, cursor:"not-allowed" };

function formatDate(isoString) {
  if (!isoString) return "";
  return new Date(isoString).toLocaleDateString("en-US", { month:"short", day:"numeric", year:"numeric" });
}

// ─── Download helper ──────────────────────────────────────────────────────────
async function downloadPost(post) {
  const zip = new JSZip();
  const parts = [];
  if (post.headline)         parts.push(`HEADLINE\n${post.headline}\n`);
  if (post.instagram)        parts.push(`INSTAGRAM CAPTION\n${post.instagram}\n`);
  if (post.linkedin)         parts.push(`LINKEDIN POST\n${post.linkedin}\n`);
  if (post.keyStats?.length) parts.push(`KEY STATS\n${post.keyStats.join("\n")}\n`);
  zip.file("post.txt", parts.join("\n"));

  const images = post.images || [];
  if (images.length > 0) {
    const imgFolder = zip.folder("images");
    for (let i = 0; i < images.length; i++) {
      const { img, title, isChart } = images[i];
      if (!img) continue;
      const safeName = (title || "image").replace(/[^a-zA-Z0-9 _-]/g, "").trim().slice(0, 40) || "image";
      const ext = isChart ? "png" : (() => {
        try {
          const pathname = new URL(img).pathname;
          const file = pathname.split("/").pop();
          const candidate = file.includes(".") ? file.split(".").pop().toLowerCase() : "";
          return /^(jpg|jpeg|png|gif|webp|avif)$/.test(candidate) ? candidate : "jpg";
        } catch { return "jpg"; }
      })();
      const filename = `${String(i + 1).padStart(2, "0")}_${safeName}.${ext}`;
      if (img.startsWith("data:")) {
        imgFolder.file(filename, img.split(",")[1], { base64: true });
      } else {
        try {
          const resp = await fetch(img);
          if (resp.ok) imgFolder.file(filename, await resp.blob());
        } catch (_) { /* skip CORS-blocked */ }
      }
    }
  }

  const blob = await zip.generateAsync({ type: "blob" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  const date = post.savedAt ? post.savedAt.slice(0, 10) : (post.createdTime || "").slice(0, 10) || "post";
  a.download = `mw_post_${date}.zip`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(a.href);
}

// ─── Edit modal (Needs Review only) ──────────────────────────────────────────
function EditModal({ post, onClose, onSaved, onApproved }) {
  const [instagram,  setInstagram]  = useState(post.instagram  || "");
  const [linkedin,   setLinkedIn]   = useState(post.linkedin   || "");
  const [tab,        setTab]        = useState(post.instagram ? "instagram" : "linkedin");
  const [saveState,  setSaveState]  = useState("idle"); // idle | saving | approving | saved | error
  const [errorMsg,   setErrorMsg]   = useState("");

  // Close on Escape
  useEffect(() => {
    const handler = e => { if (e.key === "Escape") onClose(); };
    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, [onClose]);

  // Lock body scroll
  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = ""; };
  }, []);

  function buildUpdatedContent() {
    return { ...post, instagram, linkedin, editedAt: new Date().toISOString() };
  }

  async function save() {
    setSaveState("saving"); setErrorMsg("");
    try {
      const res = await fetch(`/posts/${post.fileId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content: buildUpdatedContent() }),
      });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);
      setSaveState("saved");
      onSaved(buildUpdatedContent());
      setTimeout(() => setSaveState("idle"), 2000);
    } catch (e) {
      setErrorMsg(e.message); setSaveState("error");
    }
  }

  async function approve() {
    setSaveState("approving"); setErrorMsg("");
    try {
      const res = await fetch(`/posts/${post.fileId}/approve`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content: buildUpdatedContent() }),
      });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);
      onApproved(post.fileId);
    } catch (e) {
      setErrorMsg(e.message); setSaveState("error");
    }
  }

  const busy = saveState === "saving" || saveState === "approving";

  return (
    <div
      onClick={e => { if (e.target === e.currentTarget) onClose(); }}
      style={{
        position: "fixed", inset: 0, zIndex: 200,
        background: "rgba(30,20,60,0.45)",
        display: "flex", alignItems: "center", justifyContent: "center",
        padding: 20,
        fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
      }}
    >
      <div style={{
        background: MW.white, borderRadius: 14,
        width: "100%", maxWidth: 660,
        maxHeight: "90vh", overflow: "hidden",
        display: "flex", flexDirection: "column",
        boxShadow: "0 24px 64px rgba(0,0,0,0.22)",
      }}>

        {/* Header */}
        <div style={{
          padding: "16px 22px", borderBottom: `0.5px solid ${MW.border}`,
          display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 12,
          flexShrink: 0,
        }}>
          <div style={{ minWidth: 0 }}>
            <div style={{ ...eyebrow, marginBottom: 4 }}>Edit Post · Needs Review</div>
            {post.headline && (
              <div style={{
                fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif",
                fontSize: 15, fontWeight: 700, color: MW.titleText, lineHeight: 1.35,
              }}>
                {post.headline}
              </div>
            )}
          </div>
          <button
            onClick={onClose}
            style={{ background: "none", border: "none", cursor: "pointer", color: MW.muted, fontSize: 22, lineHeight: 1, padding: "2px 4px", flexShrink: 0 }}
            aria-label="Close"
          >
            ×
          </button>
        </div>

        {/* Scrollable body */}
        <div style={{ overflow: "auto", flex: 1, padding: "18px 22px" }}>

          {/* Key stats */}
          {post.keyStats?.length > 0 && (
            <div style={{ display: "flex", gap: 5, flexWrap: "wrap", marginBottom: 16 }}>
              {post.keyStats.map((s, i) => (
                <span key={i} style={{
                  fontSize: 11, padding: "3px 10px",
                  background: MW.purpleBg, border: `0.5px solid ${MW.softLav}`,
                  borderRadius: 20, color: MW.purple,
                }}>
                  {s}
                </span>
              ))}
            </div>
          )}

          {/* Platform tabs */}
          <div style={{ display: "flex", gap: 5, marginBottom: 14 }}>
            {post.instagram !== undefined && (
              <button onClick={() => setTab("instagram")} style={tab === "instagram" ? btnActive : btnBase}>
                Instagram
              </button>
            )}
            {post.linkedin !== undefined && (
              <button onClick={() => setTab("linkedin")} style={tab === "linkedin" ? btnActive : btnBase}>
                LinkedIn
              </button>
            )}
          </div>

          {/* Character count hint */}
          <div style={{ fontSize: 11, color: MW.muted, marginBottom: 8, textAlign: "right" }}>
            {tab === "instagram"
              ? `${instagram.length} chars`
              : `${linkedin.length} chars`}
          </div>

          {/* Instagram textarea */}
          {tab === "instagram" && (
            <textarea
              value={instagram}
              onChange={e => { setInstagram(e.target.value); if (saveState === "saved") setSaveState("idle"); }}
              style={{
                width: "100%", minHeight: 260, boxSizing: "border-box",
                border: `0.5px solid ${MW.border}`, borderRadius: 6,
                padding: "12px 14px", fontSize: 13,
                fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
                color: MW.bodyText, background: MW.white,
                resize: "vertical", outline: "none", lineHeight: 1.75,
              }}
              onFocus={e => { e.target.style.borderColor = MW.purple; }}
              onBlur={e  => { e.target.style.borderColor = MW.border; }}
            />
          )}

          {/* LinkedIn textarea */}
          {tab === "linkedin" && (
            <textarea
              value={linkedin}
              onChange={e => { setLinkedIn(e.target.value); if (saveState === "saved") setSaveState("idle"); }}
              style={{
                width: "100%", minHeight: 260, boxSizing: "border-box",
                border: `0.5px solid ${MW.border}`, borderRadius: 6,
                padding: "12px 14px", fontSize: 13,
                fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
                color: MW.bodyText, background: MW.white,
                resize: "vertical", outline: "none", lineHeight: 1.75,
              }}
              onFocus={e => { e.target.style.borderColor = MW.purple; }}
              onBlur={e  => { e.target.style.borderColor = MW.border; }}
            />
          )}

          {errorMsg && (
            <div style={{ fontSize: 12, color: MW.callout, marginTop: 10, padding: "8px 12px", background: "#FFF5F5", border: `0.5px solid #F9C9C0`, borderRadius: 4 }}>
              {errorMsg}
            </div>
          )}
        </div>

        {/* Footer */}
        <div style={{
          padding: "14px 22px", borderTop: `0.5px solid ${MW.border}`,
          display: "flex", justifyContent: "space-between", alignItems: "center",
          flexShrink: 0, background: MW.rowAlt, gap: 10,
        }}>
          <button onClick={onClose} style={btnBase}>Cancel</button>
          <div style={{ display: "flex", gap: 8 }}>
            <button
              onClick={save}
              disabled={busy}
              style={busy ? btnDisabled : saveState === "saved" ? { ...btnBase, color: MW.green, borderColor: MW.green } : btnBase}
            >
              {saveState === "saving" ? "Saving…" : saveState === "saved" ? "Saved ✓" : "Save changes"}
            </button>
            <button onClick={approve} disabled={busy} style={busy ? btnDisabled : btnPrimary}>
              {saveState === "approving" ? "Approving…" : "Move to Approved →"}
            </button>
          </div>
        </div>

      </div>
    </div>
  );
}

// ─── Single post card ─────────────────────────────────────────────────────────
function PostCard({ post, onDownload, onSelect }) {
  const hasIG = Boolean(post.instagram);
  const hasLI = Boolean(post.linkedin);
  const [tab, setTab]                 = useState(hasIG ? "instagram" : "linkedin");
  const [downloading, setDownloading] = useState(false);

  const platformBadge = (platform, active) => {
    const isIG = platform === "instagram";
    const activeColor  = isIG ? "#E1306C" : "#0A66C2";
    const activeBg     = isIG ? "#FDE8F0" : "#E8F0F9";
    const activeBorder = isIG ? "#F0A0C0" : "#A0C0E8";
    return (
      <span
        onClick={e => { e.stopPropagation(); (hasIG && hasLI) && setTab(platform); }}
        style={{
          fontSize: 10, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase",
          padding: "3px 9px", borderRadius: 12,
          cursor: (hasIG && hasLI) ? "pointer" : "default",
          background: active ? activeBg  : MW.rowAlt,
          color:      active ? activeColor : MW.muted,
          border:     `0.5px solid ${active ? activeBorder : MW.border}`,
          transition: "all 0.12s",
        }}
      >
        {isIG ? "IG" : "LI"}
      </span>
    );
  };

  const bodyText = tab === "instagram" ? post.instagram : post.linkedin;

  return (
    <div
      onClick={onSelect ? () => onSelect(post) : undefined}
      style={{
        background: MW.white, border: `0.5px solid ${MW.border}`,
        borderRadius: 12, padding: "18px 20px",
        display: "flex", flexDirection: "column", gap: 11,
        transition: "box-shadow 0.15s, border-color 0.15s",
        cursor: onSelect ? "pointer" : "default",
      }}
      onMouseEnter={e => {
        e.currentTarget.style.boxShadow = "0 4px 18px rgba(91,79,190,0.10)";
        if (onSelect) e.currentTarget.style.borderColor = MW.softLav;
      }}
      onMouseLeave={e => {
        e.currentTarget.style.boxShadow = "none";
        e.currentTarget.style.borderColor = MW.border;
      }}
    >
      {/* Top row: platform badges + date */}
      <div style={{ display:"flex", justifyContent:"space-between", alignItems:"center" }}>
        <div style={{ display:"flex", gap:5 }}>
          {hasIG && platformBadge("instagram", tab === "instagram")}
          {hasLI && platformBadge("linkedin",  tab === "linkedin")}
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          {onSelect && (
            <span style={{ fontSize: 10, color: MW.lavender, letterSpacing: "0.05em" }}>
              click to edit
            </span>
          )}
          <span style={{ fontSize:11, color:MW.muted }}>
            {formatDate(post.savedAt || post.createdTime)}
          </span>
        </div>
      </div>

      {/* Headline */}
      {post.headline && (
        <div style={{
          fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif",
          fontSize: 15, fontWeight: 700, color: MW.titleText, lineHeight: 1.4,
        }}>
          {post.headline}
        </div>
      )}

      {/* Key stats pills */}
      {post.keyStats?.length > 0 && (
        <div style={{ display:"flex", gap:5, flexWrap:"wrap" }}>
          {post.keyStats.map((s, i) => (
            <span key={i} style={{
              fontSize: 11, padding: "3px 10px",
              background: MW.purpleBg, border: `0.5px solid ${MW.softLav}`,
              borderRadius: 20, color: MW.purple,
            }}>
              {s}
            </span>
          ))}
        </div>
      )}

      {/* Post body preview */}
      {bodyText && (
        <div style={{
          fontSize: 12, color: MW.bodyText, lineHeight: 1.7,
          display: "-webkit-box", WebkitLineClamp: 5, WebkitBoxOrient: "vertical",
          overflow: "hidden", flex: 1,
        }}>
          {bodyText}
        </div>
      )}

      {/* Footer */}
      <div style={{
        paddingTop: 10, borderTop: `0.5px solid ${MW.border}`,
        display: "flex", justifyContent: "space-between", alignItems: "flex-end", gap: 8,
      }}>
        {post.question && (
          <div style={{
            fontSize: 11, color: MW.muted, fontStyle: "italic",
            flex: 1, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap",
          }}>
            {post.question}
          </div>
        )}
        {post.url && (
          <a
            href={post.url} target="_blank" rel="noreferrer"
            onClick={e => e.stopPropagation()}
            style={{ fontSize: 11, color: MW.purple, textDecoration: "none", whiteSpace: "nowrap", flexShrink: 0 }}
          >
            Open in Drive →
          </a>
        )}
      </div>

      {/* Download button — approved posts only */}
      {onDownload && (
        <button
          disabled={downloading}
          onClick={async e => {
            e.stopPropagation();
            setDownloading(true);
            try { await onDownload(); } finally { setDownloading(false); }
          }}
          style={{
            width: "100%", marginTop: 2, padding: "7px 0",
            background: downloading ? MW.rowAlt : MW.purpleBg,
            border: `0.5px solid ${downloading ? MW.border : MW.softLav}`,
            borderRadius: 4, cursor: downloading ? "default" : "pointer",
            fontSize: 12, fontWeight: 500,
            fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
            color: downloading ? MW.muted : MW.purple,
            letterSpacing: "0.02em", transition: "all 0.15s",
          }}
        >
          {downloading ? "Preparing download…" : "Download ↓"}
        </button>
      )}
    </div>
  );
}

// ─── Gallery page ─────────────────────────────────────────────────────────────
export default function PostsGallery() {
  const [folder,       setFolder]       = useState("needs_review");
  const [posts,        setPosts]        = useState([]);
  const [loading,      setLoading]      = useState(false);
  const [error,        setError]        = useState(null);
  const [selectedPost, setSelectedPost] = useState(null);

  useEffect(() => {
    setLoading(true); setError(null); setPosts([]);
    fetch(`/posts?folder=${folder}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) throw new Error(data.error);
        setPosts(data.posts || []);
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  }, [folder]);

  function handleSaved(updatedPost) {
    setPosts(prev => prev.map(p => p.fileId === updatedPost.fileId ? { ...p, ...updatedPost } : p));
    setSelectedPost(prev => prev?.fileId === updatedPost.fileId ? { ...prev, ...updatedPost } : prev);
  }

  function handleApproved(fileId) {
    setPosts(prev => prev.filter(p => p.fileId !== fileId));
    setSelectedPost(null);
  }

  return (
    <div style={{
      fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
      background: "#F8F8FB", minHeight: "100vh", padding: "32px 16px",
    }}>
      <div style={{ maxWidth: 1100, margin: "0 auto" }}>

        {/* Page header */}
        <div style={{ marginBottom: 28 }}>
          <div style={eyebrow}>Post Gallery · Google Drive</div>
          <div style={{ display:"flex", justifyContent:"space-between", alignItems:"flex-end", flexWrap:"wrap", gap:12 }}>
            <h2 style={{
              fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif",
              fontSize: 26, fontWeight: 900, margin: 0, color: MW.titleText,
            }}>
              Saved <span style={{ color: MW.purple }}>Posts</span>
            </h2>
            <div style={{ display:"flex", gap:6 }}>
              {[["needs_review","Needs Review"], ["approved","Approved"]].map(([val, label]) => (
                <button key={val} onClick={() => setFolder(val)} style={folder === val ? btnActive : btnBase}>
                  {label}
                </button>
              ))}
            </div>
          </div>
          <div style={{ width:36, height:3, background:MW.purple, marginTop:8 }} />
        </div>

        {/* Loading */}
        {loading && (
          <div style={{ textAlign:"center", padding:"60px 0", color:MW.muted, fontSize:13 }}>
            Loading posts from Drive…
          </div>
        )}

        {/* Error */}
        {!loading && error && (
          <div style={{ padding:"14px 18px", background:"#FFF5F5", border:`0.5px solid ${MW.callout}`, borderRadius:8, color:MW.callout, fontSize:13 }}>
            {error}
          </div>
        )}

        {/* Empty state */}
        {!loading && !error && posts.length === 0 && (
          <div style={{ textAlign:"center", padding:"60px 0", color:MW.muted, fontSize:13 }}>
            No posts found in the <strong>{folder === "approved" ? "Approved" : "Needs Review"}</strong> folder.
          </div>
        )}

        {/* Post count + grid */}
        {posts.length > 0 && (
          <>
            <div style={{ fontSize:12, color:MW.muted, marginBottom:16 }}>
              {posts.length} post{posts.length !== 1 ? "s" : ""}
              {folder === "needs_review" && (
                <span style={{ color: MW.lavender, marginLeft: 8 }}>· click any post to edit or approve</span>
              )}
            </div>
            <div style={{ display:"grid", gridTemplateColumns:"repeat(auto-fill, minmax(300px, 1fr))", gap:16 }}>
              {posts.map((post, i) => (
                <PostCard
                  key={post.fileId || i}
                  post={post}
                  onDownload={folder === "approved" ? () => downloadPost(post) : null}
                  onSelect={folder === "needs_review" ? setSelectedPost : null}
                />
              ))}
            </div>
          </>
        )}

        <div style={{ textAlign:"center", marginTop:40, paddingTop:20, borderTop:`0.5px solid ${MW.border}` }}>
          <div style={{ fontSize:10, letterSpacing:"0.12em", textTransform:"uppercase", color:MW.muted }}>
            Masterworks · Art Market Intelligence
          </div>
        </div>

      </div>

      {/* Edit modal */}
      {selectedPost && (
        <EditModal
          post={selectedPost}
          onClose={() => setSelectedPost(null)}
          onSaved={handleSaved}
          onApproved={handleApproved}
        />
      )}
    </div>
  );
}
