import { useState, useEffect } from "react";

const MW = {
  purple: "#5B4FBE", lavender: "#8B83D0", softLav: "#C8C4E8",
  purpleBg: "#EEEDFE", titleText: "#444444", bodyText: "#333333",
  subText: "#555555", muted: "#999999", border: "#E5E3F5",
  rowAlt: "#F5F7FA", green: "#2D7A16", callout: "#E85D3A",
  white: "#FFFFFF",
};

const eyebrow   = { fontSize: 10, fontWeight: 500, letterSpacing: "0.1em", textTransform: "uppercase", color: MW.purple, marginBottom: 10, fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif" };
const card      = { background: MW.white, border: `0.5px solid ${MW.border}`, borderRadius: 12, padding: "22px 24px", marginBottom: 16 };
const btnBase   = { border: `1px solid ${MW.border}`, borderRadius: 4, background: MW.white, cursor: "pointer", fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif", fontSize: 12, color: MW.muted, padding: "6px 14px" };
const btnActive = { ...btnBase, border: `1px solid ${MW.purple}`, background: MW.purpleBg, color: MW.purple };
const btnPrimary  = { padding: "8px 28px", background: MW.purple, color: MW.white, border: "none", borderRadius: 4, cursor: "pointer", fontSize: 13, fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif" };
const btnDisabled = { ...btnPrimary, background: MW.rowAlt, color: MW.muted, cursor: "not-allowed" };

const SCHEMA_HINT = `{
  "headline": "Basquiat Breaks Records as Top Lots Beat Estimates",
  "instagram": "Caption text…\\n\\n#art #investing",
  "linkedin":  "LinkedIn post text…",
  "keyStats":  ["$18.5M top lot", "+34% avg premium", "92% sell-through"],
  "question":  "The prompt that produced this post (optional)",
  "images":    [{ "img": "https://…", "title": "Untitled", "isChart": false }]
}`;

// ── Editable text block (shared by IG and LI) ─────────────────────────────────
function PostTextBlock({ label, original, edited, onEdit, isEditing, onToggleEdit, copyKey, copied, onCopy }) {
  const text = edited ?? original;
  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 12 }}>
        <div style={{ ...eyebrow, marginBottom: 0 }}>{label}</div>
        <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
          {edited !== null && (
            <span style={{ fontSize: 10, color: MW.purple, background: MW.purpleBg, border: `0.5px solid ${MW.softLav}`, borderRadius: 10, padding: "2px 8px", letterSpacing: "0.05em" }}>
              edited
            </span>
          )}
          <button onClick={() => onCopy(text, copyKey)} style={btnBase}>
            {copied === copyKey ? "Copied ✓" : "Copy"}
          </button>
          <button onClick={onToggleEdit} style={isEditing ? btnActive : btnBase}>
            {isEditing ? "Done editing" : "Edit"}
          </button>
        </div>
      </div>
      {isEditing ? (
        <textarea
          value={text}
          onChange={e => onEdit(e.target.value)}
          style={{
            width: "100%", minHeight: 220, boxSizing: "border-box",
            border: `0.5px solid ${MW.purple}`, borderRadius: 6,
            padding: "12px 14px", fontSize: 13,
            fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif",
            color: MW.bodyText, background: MW.white,
            resize: "vertical", outline: "none", lineHeight: 1.75,
          }}
        />
      ) : (
        <div style={{
          fontSize: 13, color: MW.bodyText, lineHeight: 1.8,
          whiteSpace: "pre-wrap", background: MW.rowAlt,
          border: `0.5px solid ${MW.border}`, borderRadius: 6,
          padding: "14px 16px",
        }}>
          {text}
        </div>
      )}
    </div>
  );
}

// ── Main page ─────────────────────────────────────────────────────────────────
export default function LocalDraft() {
  const [draft,            setDraft]            = useState(null);
  const [loadError,        setLoadError]        = useState(null);
  const [loading,          setLoading]          = useState(true);
  const [tab,              setTab]              = useState("instagram");
  const [editedInstagram,  setEditedInstagram]  = useState(null);
  const [editedLinkedIn,   setEditedLinkedIn]   = useState(null);
  const [editing,          setEditing]          = useState(null);
  const [approvalStatus,   setApprovalStatus]   = useState(null);
  const [saveState,        setSaveState]        = useState("idle");
  const [saveUrl,          setSaveUrl]          = useState(null);
  const [showPaste,        setShowPaste]        = useState(false);
  const [pasteValue,       setPasteValue]       = useState("");
  const [pasteError,       setPasteError]       = useState("");
  const [copied,           setCopied]           = useState("");

  useEffect(() => { loadFromFile(); }, []);

  async function loadFromFile() {
    setLoading(true);
    setLoadError(null);
    try {
      const res  = await fetch("/local-draft");
      const data = await res.json();
      if (!res.ok) { setLoadError(data.error || "Failed to load draft."); setDraft(null); }
      else          applyDraft(data);
    } catch {
      setLoadError("Could not reach the server.");
    }
    setLoading(false);
  }

  function applyDraft(data) {
    setDraft(data);
    setTab(data.instagram ? "instagram" : "linkedin");
    setEditedInstagram(null);
    setEditedLinkedIn(null);
    setEditing(null);
    setApprovalStatus(null);
    setSaveState("idle");
    setSaveUrl(null);
  }

  function loadFromPaste() {
    setPasteError("");
    try {
      const data = JSON.parse(pasteValue);
      if (!data.instagram && !data.linkedin) {
        setPasteError("JSON must include an 'instagram' or 'linkedin' field.");
        return;
      }
      applyDraft(data);
      setShowPaste(false);
      setPasteValue("");
      setLoadError(null);
    } catch {
      setPasteError("Invalid JSON — check the format and try again.");
    }
  }

  async function saveToDrive() {
    if (!approvalStatus || !draft) return;
    setSaveState("saving");
    setSaveUrl(null);
    try {
      const res = await fetch("/save", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          status: approvalStatus,
          payload: {
            ...draft,
            instagram: editedInstagram ?? draft.instagram,
            linkedin:  editedLinkedIn  ?? draft.linkedin,
            savedAt:   new Date().toISOString(),
            source:    "local_draft",
          },
        }),
      });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);
      setSaveState("saved");
      setSaveUrl(json.url || null);
    } catch {
      setSaveState("error");
    }
  }

  function copy(text, key) {
    navigator.clipboard.writeText(text);
    setCopied(key);
    setTimeout(() => setCopied(""), 2000);
  }

  function toggleEdit(platform) {
    if (editing === platform) {
      setEditing(null);
    } else {
      setEditing(platform);
      if (platform === "instagram" && editedInstagram === null) setEditedInstagram(draft.instagram);
      if (platform === "linkedin"  && editedLinkedIn  === null) setEditedLinkedIn(draft.linkedin);
    }
  }

  return (
    <div style={{ fontFamily: "Helvetica Neue, Helvetica, Arial, sans-serif", background: "#F8F8FB", minHeight: "100vh", padding: "32px 16px" }}>
      <div style={{ maxWidth: 720, margin: "0 auto" }}>

        {/* Page header */}
        <div style={{ marginBottom: 28 }}>
          <div style={eyebrow}>Local Draft · Claude Code</div>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", flexWrap: "wrap", gap: 12 }}>
            <h2 style={{ fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif", fontSize: 26, fontWeight: 900, margin: 0, color: MW.titleText }}>
              Draft <span style={{ color: MW.purple }}>Post</span>
            </h2>
            <div style={{ display: "flex", gap: 6 }}>
              <button onClick={loadFromFile} disabled={loading} style={btnBase}>
                {loading ? "Loading…" : "↻ Reload"}
              </button>
              <button
                onClick={() => { setShowPaste(s => !s); setPasteError(""); }}
                style={showPaste ? btnActive : btnBase}
              >
                Paste JSON
              </button>
            </div>
          </div>
          <div style={{ width: 36, height: 3, background: MW.purple, marginTop: 8 }} />
        </div>

        {/* Paste panel */}
        {showPaste && (
          <div style={card}>
            <div style={eyebrow}>Paste JSON from Claude Code</div>
            <textarea
              value={pasteValue}
              onChange={e => { setPasteValue(e.target.value); setPasteError(""); }}
              placeholder={SCHEMA_HINT}
              style={{
                width: "100%", minHeight: 140, boxSizing: "border-box",
                border: `0.5px solid ${MW.border}`, borderRadius: 4,
                padding: "10px 12px", fontSize: 11,
                fontFamily: "ui-monospace, 'Cascadia Code', monospace",
                color: MW.bodyText, background: MW.rowAlt,
                resize: "vertical", outline: "none", lineHeight: 1.6,
              }}
            />
            {pasteError && (
              <div style={{ fontSize: 12, color: MW.callout, marginTop: 6 }}>{pasteError}</div>
            )}
            <button
              onClick={loadFromPaste}
              disabled={!pasteValue.trim()}
              style={{ ...(!pasteValue.trim() ? btnDisabled : btnPrimary), marginTop: 10 }}
            >
              Load →
            </button>
          </div>
        )}

        {/* Loading */}
        {loading && (
          <div style={{ textAlign: "center", padding: "60px 0", color: MW.muted, fontSize: 13 }}>
            Loading draft…
          </div>
        )}

        {/* Empty / error state */}
        {!loading && !draft && (
          <div style={card}>
            {loadError && (
              <div style={{ fontSize: 13, color: MW.callout, marginBottom: 20, padding: "10px 14px", background: "#FFF5F5", border: `0.5px solid #F9C9C0`, borderRadius: 6 }}>
                {loadError}
              </div>
            )}
            <div style={{ ...eyebrow, marginBottom: 10 }}>How to use this page</div>
            <p style={{ fontSize: 13, color: MW.bodyText, lineHeight: 1.8, margin: "0 0 10px" }}>
              Ask Claude Code to write a post draft to{" "}
              <code style={{ background: MW.rowAlt, border: `0.5px solid ${MW.border}`, borderRadius: 3, padding: "1px 6px", fontSize: 12 }}>
                local_draft.json
              </code>{" "}
              in the project root, then click <strong>↻ Reload</strong>.
            </p>
            <p style={{ fontSize: 12, color: MW.muted, lineHeight: 1.7, margin: "0 0 20px" }}>
              Alternatively, click <strong>Paste JSON</strong> above to load content directly from your conversation.
            </p>
            <div style={{ ...eyebrow, marginBottom: 8 }}>Expected format</div>
            <pre style={{
              background: MW.rowAlt, border: `0.5px solid ${MW.border}`, borderRadius: 6,
              padding: "12px 14px", fontSize: 11,
              fontFamily: "ui-monospace, 'Cascadia Code', monospace",
              color: MW.bodyText, overflowX: "auto", margin: 0, lineHeight: 1.7,
            }}>
              {SCHEMA_HINT}
            </pre>
          </div>
        )}

        {/* ── Draft loaded ── */}
        {!loading && draft && (
          <>
            {/* Headline + key stats + question */}
            {(draft.headline || draft.keyStats?.length > 0 || draft.question) && (
              <div style={card}>
                {draft.headline && (
                  <div style={{
                    fontFamily: "'Playfair Display','DM Serif Display',Georgia,serif",
                    fontSize: 19, fontWeight: 700, color: MW.titleText, lineHeight: 1.4,
                    marginBottom: draft.keyStats?.length > 0 ? 14 : 0,
                  }}>
                    {draft.headline}
                  </div>
                )}
                {draft.keyStats?.length > 0 && (
                  <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>
                    {draft.keyStats.map((s, i) => (
                      <span key={i} style={{ fontSize: 11, padding: "3px 10px", background: MW.purpleBg, border: `0.5px solid ${MW.softLav}`, borderRadius: 20, color: MW.purple }}>
                        {s}
                      </span>
                    ))}
                  </div>
                )}
                {draft.question && (
                  <div style={{ marginTop: 14, paddingTop: 12, borderTop: `0.5px solid ${MW.border}`, fontSize: 11, color: MW.muted, fontStyle: "italic" }}>
                    {draft.question}
                  </div>
                )}
              </div>
            )}

            {/* Images */}
            {draft.images?.length > 0 && (
              <div style={card}>
                <div style={eyebrow}>Images</div>
                <div style={{ display: "flex", gap: 12, flexWrap: "wrap" }}>
                  {draft.images.map((img, i) => (
                    <div key={i} style={{ flex: "1 1 200px", minWidth: 0 }}>
                      <img
                        src={img.img}
                        alt={img.title}
                        style={{ width: "100%", borderRadius: 6, border: `0.5px solid ${MW.border}`, display: "block", objectFit: "cover", aspectRatio: "1 / 1" }}
                      />
                      <div style={{ fontSize: 11, color: MW.subText, marginTop: 6, lineHeight: 1.4 }}>{img.title}</div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Post text */}
            <div style={card}>
              {/* Tab switcher */}
              <div style={{ display: "flex", gap: 5, marginBottom: 18 }}>
                {draft.instagram && (
                  <button onClick={() => { setTab("instagram"); setEditing(null); }} style={tab === "instagram" ? btnActive : btnBase}>
                    Instagram
                  </button>
                )}
                {draft.linkedin && (
                  <button onClick={() => { setTab("linkedin"); setEditing(null); }} style={tab === "linkedin" ? btnActive : btnBase}>
                    LinkedIn
                  </button>
                )}
              </div>

              {tab === "instagram" && draft.instagram && (
                <PostTextBlock
                  label="Instagram Caption"
                  original={draft.instagram}
                  edited={editedInstagram}
                  onEdit={setEditedInstagram}
                  isEditing={editing === "instagram"}
                  onToggleEdit={() => toggleEdit("instagram")}
                  copyKey="ig"
                  copied={copied}
                  onCopy={copy}
                />
              )}

              {tab === "linkedin" && draft.linkedin && (
                <PostTextBlock
                  label="LinkedIn Post"
                  original={draft.linkedin}
                  edited={editedLinkedIn}
                  onEdit={setEditedLinkedIn}
                  isEditing={editing === "linkedin"}
                  onToggleEdit={() => toggleEdit("linkedin")}
                  copyKey="li"
                  copied={copied}
                  onCopy={copy}
                />
              )}
            </div>

            {/* Save to Drive */}
            <div style={card}>
              <div style={eyebrow}>Save to Drive</div>
              <div style={{ display: "flex", gap: 6, marginBottom: 16 }}>
                {[["needs_review", "Needs Review"], ["approved", "Approved"]].map(([val, label]) => (
                  <button key={val} onClick={() => { setApprovalStatus(val); setSaveState("idle"); }} style={approvalStatus === val ? btnActive : btnBase}>
                    {label}
                  </button>
                ))}
              </div>
              <button
                onClick={saveToDrive}
                disabled={!approvalStatus || saveState === "saving" || saveState === "saved"}
                style={(!approvalStatus || saveState === "saving" || saveState === "saved") ? btnDisabled : btnPrimary}
              >
                {saveState === "saving" ? "Saving…" : saveState === "saved" ? "Saved ✓" : "Save to Drive →"}
              </button>
              {saveState === "saved" && saveUrl && (
                <a href={saveUrl} target="_blank" rel="noreferrer" style={{ fontSize: 12, color: MW.purple, textDecoration: "none", display: "block", marginTop: 10 }}>
                  Open in Drive →
                </a>
              )}
              {saveState === "error" && (
                <div style={{ fontSize: 12, color: MW.callout, marginTop: 10 }}>Save failed — please try again.</div>
              )}
            </div>
          </>
        )}

        <div style={{ textAlign: "center", marginTop: 40, paddingTop: 20, borderTop: `0.5px solid ${MW.border}` }}>
          <div style={{ fontSize: 10, letterSpacing: "0.12em", textTransform: "uppercase", color: MW.muted }}>
            Masterworks · Art Market Intelligence
          </div>
        </div>

      </div>
    </div>
  );
}
