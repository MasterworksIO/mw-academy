import { useState, useEffect, useRef } from "react";
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell, ReferenceLine,
         LineChart, Line, Legend } from "recharts";

// ─── CONFIG ───────────────────────────────────────────────────────────────────
const QUERY_ENDPOINT = "/api/";

// ─── Masterworks Brand Tokens ─────────────────────────────────────────────────
const MW = {
  purple:   "#5B4FBE", lavender: "#8B83D0", softLav:  "#C8C4E8",
  purpleBg: "#EEEDFE", titleText:"#444444", bodyText: "#333333",
  subText:  "#555555", muted:    "#999999", border:   "#E5E3F5",
  rowAlt:   "#F5F7FA", green:    "#2D7A16", callout:  "#E85D3A",
  white:    "#FFFFFF",
};

const CHART_COLORS = ["#5B4FBE","#8B83D0","#C8C4E8","#3C3489","#7F77DD","#AFA9EC","#534AB7","#CECBF6"];
const PLATFORMS    = ["LinkedIn","Instagram","Both"];
const TONES        = ["Analytical","Editorial","Conversational"];
const PRESETS      = [
  "Top lots by hammer price this year",
  "Top lots by hammer price last 90 days",
  "Biggest premiums over high estimate in last 12 months",
  "Recent Christie's sales",
  "Recent Sotheby's sales",
  "Recent Phillips sales",
  "Art market vs S&P 500 correlation last 5 years",
  "Art market index performance since 2020",
];

const fmt = n =>
  !n ? "N/A" : n >= 1e6 ? `$${(n/1e6).toFixed(1)}M` : n >= 1e3 ? `$${(n/1e3).toFixed(0)}K` : `$${Math.round(n)}`;

// Parse fetch response as JSON; if the body is plain text (e.g. a proxy timeout
// message like "first byte timeout"), throw it as a readable Error instead of
// letting JSON.parse throw an opaque syntax error.
async function safeJson(res) {
  const text = await res.text();
  try { return JSON.parse(text); } catch { throw new Error(text.slice(0, 120)); }
}

// ─── Data Analysis (runs on main answer rows) ─────────────────────────────────
function analyzeRows(rows, columns, question = "") {
  const idx = k => columns.indexOf(k);
  const get = (r, k) => r[idx(k)];
  const sold = rows.filter(r => get(r,"lot_status")==="sold" && get(r,"hammer_price_usd"));
  const total = sold.reduce((s,r) => s + +get(r,"hammer_price_usd"), 0);
  const sellThru = rows.length ? ((sold.length/rows.length)*100).toFixed(0) : 0;
  const premiums = sold.filter(r => +get(r,"estimate_high_usd")>0)
    .map(r => ((+get(r,"hammer_price_usd") - +get(r,"estimate_high_usd")) / +get(r,"estimate_high_usd") * 100));
  const avgPremium = premiums.length ? premiums.reduce((a,b)=>a+b,0)/premiums.length : 0;
  const topLot = [...sold].sort((a,b) => +get(b,"hammer_price_usd") - +get(a,"hammer_price_usd"))[0];
  const byArtist = {};
  sold.forEach(r => {
    const a = get(r,"artist_name");
    if (a) {
      if (!byArtist[a]) byArtist[a] = {total:0,count:0};
      byArtist[a].total += +get(r,"hammer_price_usd");
      byArtist[a].count++;
    }
  });
  const artistChart = Object.entries(byArtist)
    .map(([k,v]) => ({name:k.split(" ").pop(), full:k, total:v.total, count:v.count}))
    .sort((a,b) => b.total-a.total).slice(0,8);
  const premiumChart = sold.filter(r => +get(r,"estimate_high_usd")>0).map(r => ({
    name:    get(r,"artist_name")?.split(" ").pop() || "",
    full:    get(r,"artist_name") || "",
    title:   get(r,"work_title") || "",
    premium: +((+get(r,"hammer_price_usd")-+get(r,"estimate_high_usd"))/+get(r,"estimate_high_usd")*100).toFixed(1),
    hammer:  +get(r,"hammer_price_usd"),
  })).sort((a,b) => b.premium-a.premium).slice(0,10);

  // ── Timeseries ──
  const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const fmtD = d => { if (!d) return ''; const [y,m] = d.split('-'); return `${MONTHS[+m-1]} '${y.slice(2)}`; };
  const timeseriesData = sold
    .filter(r => get(r,"start_date") && get(r,"hammer_price_usd"))
    .map(r => ({
      date:        get(r,"start_date")?.slice(0,10),
      displayDate: fmtD(get(r,"start_date")?.slice(0,7)),
      hammer:      +get(r,"hammer_price_usd"),
      title:       get(r,"work_title") || "Untitled",
      artist:      get(r,"artist_name") || "",
      house:       get(r,"auction_house") || "",
    }))
    .sort((a,b) => a.date?.localeCompare(b.date));

  // ── Auto-detect best chart ──
  const uniqueArtists = [...new Set(sold.map(r => get(r,"artist_name")).filter(Boolean))];
  const q = question.toLowerCase();
  const isPremiumQuery = ["premium","over estimate","over high","biggest premium"].some(kw => q.includes(kw));
  const autoChartType  = uniqueArtists.length === 1 ? "timeseries" : isPremiumQuery ? "premium" : "revenue";

  return {sold, total, sellThru, avgPremium, topLot, artistChart, premiumChart, timeseriesData, uniqueArtists, autoChartType, get};
}

// ─── Build image lots array from sidecar data ─────────────────────────────────
function parseImageLots(imageRows, imageColumns) {
  if (!imageColumns?.length || !imageRows?.length) return [];
  const idx = k => imageColumns.indexOf(k);
  return imageRows
    .map(r => ({
      artist:    r[idx("artist_name")],
      title:     r[idx("work_title")],
      hammer:    r[idx("hammer_price_usd")],
      estHigh:   r[idx("estimate_high_usd")],
      house:     r[idx("auction_house")],
      date:      r[idx("start_date")]?.slice(0,10),
      img:       r[idx("internal_image_url")],
    }))
    .filter(l => l.img);
}

// ─── Shared Styles ────────────────────────────────────────────────────────────
const card      = {background:MW.white, border:`0.5px solid ${MW.border}`, borderRadius:12, padding:"20px 24px", marginBottom:16};
const eyebrow   = {fontSize:10, fontWeight:500, letterSpacing:"0.1em", textTransform:"uppercase", color:MW.purple, marginBottom:10, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif"};
const btnBase   = {border:`1px solid ${MW.border}`, borderRadius:4, background:MW.white, cursor:"pointer", fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif", fontSize:12, color:MW.muted, padding:"6px 14px"};
const btnActive = {...btnBase, border:`1px solid ${MW.purple}`, background:MW.purpleBg, color:MW.purple};
const btnPrimary  = {padding:"8px 28px", background:MW.purple, color:MW.white, border:"none", borderRadius:4, cursor:"pointer", fontSize:13, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif"};
const btnDisabled = {...btnPrimary, background:MW.rowAlt, color:MW.muted, cursor:"not-allowed"};

// ─── Market chart component ──────────────────────────────────────────────────
// Colors keyed by common ticker names; falls back to CHART_COLORS for unknowns.
const MARKET_COLORS = {
  pwc:         "#5B4FBE",  // art index — Masterworks purple
  sp500:       "#2D7A16",  // S&P 500 — green
  us_bonds:    "#E8A53A",  // bonds — amber
  us_housing:  "#0095f6",  // housing — blue
  gold:        "#F4A620",  // gold — yellow-gold
  real_estate: "#E85D3A",  // real estate — coral
};

function MarketChart({ marketSeries }) {
  if (!marketSeries) return null;

  // Shape A: proper timeseries with known tickers
  if (marketSeries.tickers?.length > 0) {
    // Merge all tickers into one array by date, normalized to index=100 at first point
    const dateMap = {};
    marketSeries.tickers.forEach(t => {
      (marketSeries.series[t] || []).forEach(({ date, value }) => {
        if (!dateMap[date]) dateMap[date] = { date };
        dateMap[date][t] = value;
      });
    });
    const raw = Object.values(dateMap).sort((a, b) => a.date.localeCompare(b.date));

    // Normalize each series to 100 at its first observed point
    const baselines = {};
    marketSeries.tickers.forEach(t => {
      const first = raw.find(d => d[t] != null);
      baselines[t] = first?.[t] || 1;
    });
    const chartData = raw.map(d => {
      const pt = { label: d.date.slice(0, 7) };
      marketSeries.tickers.forEach(t => {
        if (d[t] != null) pt[t] = +((d[t] / baselines[t]) * 100).toFixed(2);
      });
      return pt;
    });

    const n = chartData.length;
    return (
      <ResponsiveContainer width="100%" height={220}>
        <LineChart data={chartData} margin={{left:0,right:8,top:4,bottom:0}}>
          <XAxis dataKey="label" tick={{fill:MW.muted,fontSize:9}} axisLine={false} tickLine={false}
            interval={Math.max(0, Math.floor(n/7)-1)}/>
          <YAxis tickFormatter={v=>`${v}`} tick={{fill:MW.muted,fontSize:10}} axisLine={false} tickLine={false} width={44}/>
          <Tooltip
            contentStyle={{background:MW.white,border:`0.5px solid ${MW.border}`,borderRadius:6,fontSize:12}}
            formatter={(v,name)=>[`${v} (indexed)`, name]}/>
          <Legend wrapperStyle={{fontSize:11,paddingTop:8}}/>
          {marketSeries.tickers.map((t,i) => (
            <Line key={t} type="monotone" dataKey={t} dot={false} strokeWidth={2}
              stroke={MARKET_COLORS[t] || CHART_COLORS[i % CHART_COLORS.length]}/>
          ))}
        </LineChart>
      </ResponsiveContainer>
    );
  }

  // Shape B: aggregate result (e.g. correlation row) — render as stat cards
  if (marketSeries.aggregates?.length > 0) {
    const agg = marketSeries.aggregates[0];
    const entries = Object.entries(agg).filter(([,v]) => v !== null).slice(0, 4);
    return (
      <div style={{display:"grid", gridTemplateColumns:`repeat(${entries.length},1fr)`, gap:12}}>
        {entries.map(([k, v]) => (
          <div key={k} style={{background:MW.purpleBg, border:`0.5px solid ${MW.softLav}`, borderRadius:8, padding:"14px 16px", textAlign:"center"}}>
            <div style={{fontSize:20, fontWeight:700, color:MW.purple}}>
              {typeof v === "number" ? (Math.abs(v) < 2 ? v.toFixed(3) : v.toLocaleString()) : v}
            </div>
            <div style={{fontSize:10, color:MW.muted, letterSpacing:"0.08em", textTransform:"uppercase", marginTop:4}}>
              {k.replace(/_/g," ")}
            </div>
          </div>
        ))}
      </div>
    );
  }

  return null;
}

// ─── Instagram helpers ───────────────────────────────────────────────────────
function renderCaption(text) {
  if (!text) return null;
  return text.split('\n').map((line, li) => (
    <span key={`l${li}`}>
      {li > 0 && <br />}
      {line.split(/(\s+)/).map((token, i) =>
        (token.startsWith('#') || token.startsWith('@'))
          ? <span key={i} style={{color:'#00376b'}}>{token}</span>
          : token
      )}
    </span>
  ));
}

function InstagramPost({ post, images = [], activeIndex = 0, onIndexChange, onRemove }) {
  const [liked, setLiked]         = useState(false);
  const [saved, setSaved]         = useState(false);
  const [expanded, setExpanded]   = useState(false);
  const [imgFailed, setImgFailed] = useState(false);

  // Reset failed flag whenever the slide changes
  useEffect(() => { setImgFailed(false); }, [activeIndex]);

  const count    = images.length;
  const current  = images[activeIndex];
  const imageUrl = current?.img;
  const go = (dir) => onIndexChange?.(Math.max(0, Math.min(count - 1, activeIndex + dir)));

  const LIKES = 2847, COMMENTS = 47;
  const showMore = (post?.length || 0) > 220;
  const caption  = expanded || !showMore ? post : post?.slice(0, 220) + '…';
  const iconBtn  = {background:'none', border:'none', cursor:'pointer', padding:4, display:'flex', alignItems:'center', lineHeight:0};
  const chevron  = {position:'absolute', top:'50%', transform:'translateY(-50%)', background:'rgba(255,255,255,0.9)', border:'none', borderRadius:'50%', width:28, height:28, display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer', boxShadow:'0 1px 4px rgba(0,0,0,0.25)', zIndex:2, padding:0};

  return (
    <div style={{maxWidth:468, margin:'0 auto', background:'#fff', border:'1px solid #dbdbdb', borderRadius:8, fontFamily:"-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif", overflow:'hidden'}}>

      {/* Header */}
      <div style={{display:'flex', alignItems:'center', padding:'12px 16px', gap:12}}>
        <div style={{background:'linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)', borderRadius:'50%', padding:2.5, width:42, height:42, boxSizing:'border-box', flexShrink:0}}>
          <div style={{width:'100%', height:'100%', borderRadius:'50%', background:'#fff', padding:2, boxSizing:'border-box'}}>
            <div style={{width:'100%', height:'100%', borderRadius:'50%', background:MW.purple, display:'flex', alignItems:'center', justifyContent:'center', fontSize:13, fontWeight:700, color:'#fff', fontFamily:'Helvetica Neue,Helvetica,Arial,sans-serif'}}>M</div>
          </div>
        </div>
        <div style={{flex:1, minWidth:0}}>
          <div style={{fontSize:13, fontWeight:600, color:'#262626'}}>masterworks</div>
          <div style={{fontSize:11, color:'#8e8e8e'}}>Art Market Intelligence</div>
        </div>
        <button style={{fontSize:13, fontWeight:600, color:'#0095f6', background:'none', border:'none', cursor:'pointer', padding:'0 8px'}}>Follow</button>
        <button style={{background:'none', border:'none', cursor:'pointer', fontSize:20, color:'#262626', lineHeight:1, padding:'0 4px'}}>•••</button>
      </div>

      {/* Image + carousel controls */}
      <div style={{position:'relative', lineHeight:0}}>
        {imageUrl && !imgFailed ? (
          <img src={imageUrl} alt={current?.title || "featured lot"}
            style={{width:'100%', aspectRatio:'1/1', objectFit:'cover', display:'block'}}
            onError={() => setImgFailed(true)}/>
        ) : (
          <div style={{width:'100%', aspectRatio:'1/1', background:`linear-gradient(135deg,${MW.purple} 0%,${MW.lavender} 60%,${MW.softLav} 100%)`, display:'flex', alignItems:'center', justifyContent:'center', flexDirection:'column', gap:12}}>
            <div style={{fontSize:64, lineHeight:1}}>🎨</div>
            <div style={{fontSize:14, color:'rgba(255,255,255,0.85)', fontFamily:'Helvetica Neue,Helvetica,Arial,sans-serif', fontWeight:600, letterSpacing:'0.12em'}}>MASTERWORKS</div>
          </div>
        )}

        {/* Remove current image */}
        {onRemove && count > 0 && (
          <button onClick={onRemove}
            style={{position:'absolute', top:8, right:8, zIndex:3, background:'rgba(0,0,0,0.5)', border:'none', borderRadius:'50%', width:26, height:26, display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer', padding:0}}>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        )}

        {/* Prev / Next arrows */}
        {count > 1 && activeIndex > 0 && (
          <button onClick={() => go(-1)} style={{...chevron, left:8}}>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#262626" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
        )}
        {count > 1 && activeIndex < count - 1 && (
          <button onClick={() => go(1)} style={{...chevron, right:8}}>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#262626" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        )}

        {/* Dot indicators */}
        {count > 1 && (
          <div style={{position:'absolute', bottom:10, left:0, right:0, display:'flex', justifyContent:'center', gap:4, zIndex:2}}>
            {images.slice(0, 10).map((_, i) => (
              <div key={i} onClick={() => onIndexChange?.(i)}
                style={{width: i === activeIndex ? 7 : 5, height: i === activeIndex ? 7 : 5, borderRadius:'50%',
                  background: i === activeIndex ? '#0095f6' : 'rgba(255,255,255,0.75)',
                  border: i === activeIndex ? 'none' : '0.5px solid rgba(0,0,0,0.2)',
                  transition:'all 0.15s', cursor:'pointer', flexShrink:0}}/>
            ))}
            {count > 10 && <div style={{fontSize:9, color:'rgba(255,255,255,0.8)', alignSelf:'center', marginLeft:2}}>+{count-10}</div>}
          </div>
        )}

        {/* Lot info overlay — hidden for captured chart images */}
        {current && !current.isChart && (
          <div style={{position:'absolute', bottom: count > 1 ? 28 : 0, left:0, right:0,
            background:'linear-gradient(transparent, rgba(0,0,0,0.55))',
            padding:'28px 14px 10px', lineHeight:1.3}}>
            <div style={{fontSize:11, fontWeight:600, color:'#fff', textShadow:'0 1px 2px rgba(0,0,0,0.4)', overflow:'hidden', whiteSpace:'nowrap', textOverflow:'ellipsis'}}>
              {current.artist}
            </div>
            <div style={{fontSize:10, color:'rgba(255,255,255,0.8)', display:'flex', justifyContent:'space-between', marginTop:2}}>
              <span style={{overflow:'hidden', whiteSpace:'nowrap', textOverflow:'ellipsis', maxWidth:'70%'}}>{current.title}</span>
              <span style={{fontWeight:600, flexShrink:0, marginLeft:8}}>{fmt(current.hammer)}</span>
            </div>
          </div>
        )}
      </div>

      {/* Actions + Caption */}
      <div style={{padding:'10px 16px 14px'}}>
        {/* Icon row */}
        <div style={{display:'flex', alignItems:'center', gap:12, marginBottom:8}}>
          <button onClick={()=>setLiked(l=>!l)} style={iconBtn}>
            {liked ? (
              <svg fill="#ed4956" height="24" viewBox="0 0 48 48" width="24"><path d="M34.6 3.1c-4.5 0-7.9 1.8-10.6 5.6-2.7-3.7-6.1-5.5-10.6-5.5C6 3.1 0 9.6 0 17.6c0 7.3 5.4 12 10.6 16.5.6.5 1.3 1.1 1.9 1.7l2.3 2c4.4 3.9 6.6 5.9 7.6 6.5.5.3 1.1.5 1.6.5s1.1-.2 1.6-.5c1-.6 2.8-2.2 7.8-6.8l2-1.8c.7-.6 1.3-1.2 2-1.7C42.7 29.6 48 25 48 17.6c0-8-6-14.5-13.4-14.5z"/></svg>
            ) : (
              <svg fill="none" height="24" width="24" viewBox="0 0 24 24" stroke="#262626" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            )}
          </button>
          <button style={iconBtn}>
            <svg fill="none" height="24" width="24" viewBox="0 0 24 24" stroke="#262626" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </button>
          <button style={iconBtn}>
            <svg fill="none" height="24" width="24" viewBox="0 0 24 24" stroke="#262626" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
          <div style={{flex:1}}/>
          <button onClick={()=>setSaved(s=>!s)} style={iconBtn}>
            <svg fill={saved?'#262626':'none'} height="24" width="24" viewBox="0 0 24 24" stroke="#262626" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          </button>
        </div>

        <div style={{fontSize:13, fontWeight:600, color:'#262626', marginBottom:6}}>
          {(LIKES + (liked?1:0)).toLocaleString()} likes
        </div>

        <div style={{fontSize:13, color:'#262626', lineHeight:1.55, marginBottom:6}}>
          <span style={{fontWeight:600}}>masterworks </span>
          {renderCaption(caption)}
          {showMore && !expanded && <span onClick={()=>setExpanded(true)} style={{color:'#8e8e8e', cursor:'pointer'}}> more</span>}
        </div>

        <div style={{fontSize:13, color:'#8e8e8e', marginBottom:4, cursor:'pointer'}}>View all {COMMENTS} comments</div>
        <div style={{fontSize:10, color:'#c7c7c7', textTransform:'uppercase', letterSpacing:'0.04em', marginBottom:12}}>Just now</div>

        <div style={{borderTop:'1px solid #efefef', display:'flex', alignItems:'center', paddingTop:10, gap:12}}>
          <div style={{width:28, height:28, borderRadius:'50%', background:'#efefef', flexShrink:0}}/>
          <input readOnly placeholder="Add a comment…" style={{flex:1, border:'none', outline:'none', fontSize:14, color:'#8e8e8e', background:'transparent', fontFamily:'inherit', cursor:'default'}}/>
          <span style={{fontSize:13, fontWeight:600, color:'#b2dffc'}}>Post</span>
        </div>
      </div>
    </div>
  );
}

// ─── App ──────────────────────────────────────────────────────────────────────
export default function App() {
  const [rows, setRows]                   = useState([]);
  const [columns, setColumns]             = useState([]);
  const [imageLots, setImageLots]         = useState([]);  // sidecar image-rich lots
  const [stats, setStats]                 = useState(null);
  const [chartType, setChartType]         = useState("revenue");
  const [lastQuestion, setLastQuestion]   = useState("");
  const [dataType, setDataType]           = useState("lot");   // "lot" | "market" | "mixed"
  const [marketSeries, setMarketSeries]   = useState(null);   // {series, tickers} or {aggregates}
  const [fetchPending, setFetchPending]   = useState(false);
  const [fetchError, setFetchError]       = useState(null);
  const [queryMode, setQueryMode]         = useState("preset");
  const [activePreset, setActivePreset]   = useState(null);
  const [customRequest, setCustomRequest] = useState("");
  const [lastSQL, setLastSQL]             = useState(null);
  const [showSQL, setShowSQL]             = useState(false);
  const [showImgSQL, setShowImgSQL]       = useState(false);
  const [lastImgSQL, setLastImgSQL]       = useState(null);
  const [platform, setPlatform]           = useState("LinkedIn");
  const [tone, setTone]                   = useState("Analytical");
  const [prompt, setPrompt]               = useState("");
  const [genLoading, setGenLoading]       = useState(false);
  const [result, setResult]               = useState(null);
  const [tab, setTab]                     = useState("linkedin");
  const [copied, setCopied]               = useState("");
  const [carouselIdx, setCarouselIdx]     = useState(0);
  const [approvalStatus, setApprovalStatus] = useState(null);  // "approved" | "needs_review" | null
  const [saveState, setSaveState]           = useState("idle"); // "idle" | "saving" | "saved" | "error"
  const [saveUrl, setSaveUrl]               = useState(null);
  const [editedInstagram, setEditedInstagram] = useState(null); // null = use generated value
  const [editedLinkedIn,  setEditedLinkedIn]  = useState(null);
  const [editing, setEditing]                 = useState(false);
  const [chartCaptured, setChartCaptured]     = useState(null); // "lot" | "market" — brief confirmation

  const lotChartRef    = useRef(null);
  const marketChartRef = useRef(null);

  const removeImageLot = (i) => {
    const next = imageLots.filter((_, idx) => idx !== i);
    const newIdx = i < carouselIdx ? carouselIdx - 1
                 : i === carouselIdx ? Math.min(carouselIdx, next.length - 1)
                 : carouselIdx;
    setImageLots(next);
    setCarouselIdx(Math.max(0, newIdx));
  };

  // ── Query ──────────────────────────────────────────────────────────────────
  const requestData = async (question) => {
    setFetchPending(true);
    setFetchError(null);
    setLastSQL(null);
    setLastImgSQL(null);
    setResult(null);
    try {
      const res  = await fetch(QUERY_ENDPOINT, {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({action:"query", question}),
      });
      const json = await safeJson(res);
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);

      const intent = json.dataType || "lot";
      setDataType(intent);
      setLastQuestion(question);
      if (json.sql)      setLastSQL(json.sql);
      if (json.imageSql) setLastImgSQL(json.imageSql);

      if (intent === "market") {
        // Pure market query — no lot rows, no images
        setStats(null);
        setRows([]);
        setColumns([]);
        setImageLots([]);
        setCarouselIdx(0);
        setMarketSeries(json.marketSeries || null);
        setChartType("timeseries");
      } else if (intent === "mixed") {
        // Combined — analyze lot rows AND store market series
        setColumns(json.columns);
        setRows(json.rows);
        const analyzed = analyzeRows(json.rows, json.columns, question);
        setStats(analyzed);
        setChartType(analyzed.autoChartType);
        setMarketSeries(json.marketSeries || null);
        const lots = parseImageLots(json.imageRows || [], json.imageColumns || []);
        setImageLots(lots);
        setCarouselIdx(0);
      } else {
        // Standard lot query
        setMarketSeries(null);
        setColumns(json.columns);
        setRows(json.rows);
        const analyzed = analyzeRows(json.rows, json.columns, question);
        setStats(analyzed);
        setChartType(analyzed.autoChartType);
        const lots = parseImageLots(json.imageRows || [], json.imageColumns || []);
        setImageLots(lots);
        setCarouselIdx(0);
      }

    } catch (e) {
      setFetchError(e.message || "Failed to load data.");
    } finally {
      setFetchPending(false);
    }
  };

  // ── Generate ───────────────────────────────────────────────────────────────
  const generate = async () => {
    if (!prompt.trim() || (!stats && !marketSeries)) return;
    setGenLoading(true);
    setResult(null);

    let dataContext;

    if (dataType === "market" && marketSeries) {
      // Market data context — summarise series and any aggregate values
      const seriesSummary = marketSeries.tickers?.length > 0
        ? marketSeries.tickers.map(t => {
            const pts = marketSeries.series[t] || [];
            const first = pts[0], last = pts[pts.length - 1];
            return `${t}: ${pts.length} observations, ${first?.date} → ${last?.date}, start ${first?.value?.toFixed(2)} → end ${last?.value?.toFixed(2)}`;
          }).join("\n")
        : "";
      const aggSummary = marketSeries.aggregates?.length > 0
        ? `\nAggregate result: ${JSON.stringify(marketSeries.aggregates[0])}`
        : "";
      dataContext = `Market data context:\n${seriesSummary}${aggSummary}\n\nUser question: ${lastQuestion}`;
    } else {
      const s = stats, get = s.get, tl = s.topLot;
      const statsCtx = `Live auction data summary:
- Total lots: ${rows.length}, Sold: ${s.sold.length}, Sell-through: ${s.sellThru}%
- Total hammer: ${fmt(s.total)}, Avg premium over high estimate: ${s.avgPremium>0?"+":""}${s.avgPremium.toFixed(1)}%
- Top lot: "${tl?get(tl,"work_title"):"N/A"}" by ${tl?get(tl,"artist_name"):"N/A"} — ${tl?fmt(+get(tl,"hammer_price_usd")):"N/A"} at ${tl?get(tl,"auction_house"):"N/A"}
- Revenue by artist: ${s.artistChart.map(a=>`${a.full} ${fmt(a.total)}`).join("; ")}
- Top premiums: ${s.premiumChart.slice(0,5).map(p=>`${p.full} "${p.title}" ${p.premium>0?"+":""}${p.premium}%`).join("; ")}`;
      const imageCtx = imageLots.length > 0
        ? `\n\nTop lots with images available (use topImageUrl from these):\n${
            imageLots.slice(0,8).map(l =>
              `- "${l.title}" by ${l.artist} | ${fmt(l.hammer)} | ${l.house} | ${l.date} | ${l.img}`
            ).join("\n")
          }`
        : "";
      dataContext = statsCtx + imageCtx;
    }

    try {
      const res  = await fetch(QUERY_ENDPOINT, {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({action:"generate", dataContext, prompt, tone, platform, dataType}),
      });
      const json = await safeJson(res);
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);
      setResult(json);
      setEditedInstagram(null);
      setEditedLinkedIn(null);
      setEditing(false);
      setApprovalStatus(null);
      setSaveState("idle");
      setSaveUrl(null);
      if (json.chartRecommendation?.includes("premium")) setChartType("premium");
      else setChartType("revenue");
      setTab(platform==="LinkedIn" ? "linkedin" : "instagram");
    } catch (e) {
      setResult({error: e.message || "Generation failed — please try again."});
    }
    setGenLoading(false);
  };

  const copy = (text, key) => {
    navigator.clipboard.writeText(text);
    setCopied(key);
    setTimeout(() => setCopied(""), 2000);
  };

  const addChartToPost = async (ref, label) => {
    const container = ref.current;
    if (!container) return;
    const svgEl = container.querySelector("svg");
    if (!svgEl) return;
    const { width, height } = svgEl.getBoundingClientRect();
    if (!width || !height) return;

    // Clone SVG, fix dimensions, and inject a white background rect
    const clone = svgEl.cloneNode(true);
    clone.setAttribute("width", width);
    clone.setAttribute("height", height);
    clone.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    const bg = document.createElementNS("http://www.w3.org/2000/svg", "rect");
    bg.setAttribute("width", width); bg.setAttribute("height", height); bg.setAttribute("fill", "#ffffff");
    clone.insertBefore(bg, clone.firstChild);

    const svgBlob = new Blob([new XMLSerializer().serializeToString(clone)], { type: "image/svg+xml" });
    const url = URL.createObjectURL(svgBlob);

    const dataUrl = await new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const dpr = window.devicePixelRatio || 1;
        const canvas = document.createElement("canvas");
        canvas.width = width * dpr; canvas.height = height * dpr;
        const ctx = canvas.getContext("2d");
        ctx.scale(dpr, dpr);
        ctx.fillStyle = "#ffffff"; ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);
        URL.revokeObjectURL(url);
        resolve(canvas.toDataURL("image/png"));
      };
      img.onerror = () => { URL.revokeObjectURL(url); resolve(null); };
      img.src = url;
    });

    if (!dataUrl) return;
    const key = ref === lotChartRef ? "lot" : "market";
    setImageLots(prev => [{ img: dataUrl, title: label, artist: "Chart", isChart: true }, ...prev]);
    setCarouselIdx(0);
    setChartCaptured(key);
    setTimeout(() => setChartCaptured(null), 2500);
  };

  const saveToDrive = async () => {
    if (!approvalStatus || !result) return;
    setSaveState("saving");
    setSaveUrl(null);
    try {
      const res = await fetch("/save", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          status: approvalStatus,
          payload: {
            ...result,
            instagram: editedInstagram ?? result.instagram,
            linkedin:  editedLinkedIn  ?? result.linkedin,
            question:  lastQuestion,
            savedAt:   new Date().toISOString(),
            dataType,
            images: imageLots.map(l => ({ img: l.img, title: l.title, isChart: !!l.isChart })),
          },
        }),
      });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);
      setSaveState("saved");
      setSaveUrl(json.url || null);
    } catch (e) {
      console.error("Save error:", e);
      setSaveState("error");
    }
  };

  // ─── Render ───────────────────────────────────────────────────────────────
  return (
    <div style={{fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif", background:"#F8F8FB", minHeight:"100vh", padding:"32px 16px"}}>
      <div style={{maxWidth:860, margin:"0 auto"}}>

        {/* Header */}
        <div style={{marginBottom:32}}>
          <div style={eyebrow}>Art Market Intelligence · Live Redshift</div>
          <h1 style={{fontFamily:"'Playfair Display','DM Serif Display',Georgia,serif", fontSize:28, fontWeight:900, margin:"0 0 8px", color:MW.titleText}}>
            Social Post <span style={{color:MW.purple}}>Generator</span>
          </h1>
          <div style={{width:36, height:3, background:MW.purple}} />
        </div>

        {/* Data Panel */}
        <div style={card}>
          <div style={eyebrow}>Load Data · <span style={{color:MW.muted, fontWeight:400, textTransform:"none", letterSpacing:0}}>
            {dataType==="market" ? "raw_pricedb.timeseries" : "core.sales_priority_artists"}
          </span></div>
          <div style={{display:"flex", gap:6, marginBottom:16}}>
            {[["preset","Presets"],["natural","Custom Request"]].map(([k,l]) => (
              <button key={k} onClick={() => setQueryMode(k)} style={queryMode===k ? btnActive : btnBase}>{l}</button>
            ))}
          </div>

          {queryMode==="preset" && (
            <div>
              <div style={{display:"flex", flexWrap:"wrap", gap:8, marginBottom:14}}>
                {PRESETS.map((p,i) => (
                  <button key={i} onClick={() => setActivePreset(i)} style={activePreset===i ? btnActive : btnBase}>{p}</button>
                ))}
              </div>
              <button onClick={() => activePreset!==null && !fetchPending && requestData(PRESETS[activePreset])}
                disabled={activePreset===null || fetchPending}
                style={activePreset!==null && !fetchPending ? btnPrimary : btnDisabled}>
                {fetchPending ? "Querying Redshift…" : "Request Data →"}
              </button>
            </div>
          )}

          {queryMode==="natural" && (
            <div style={{display:"flex", gap:8}}>
              <input value={customRequest} onChange={e => setCustomRequest(e.target.value)}
                onKeyDown={e => e.key==="Enter" && customRequest.trim() && !fetchPending && requestData(customRequest)}
                placeholder="e.g. Basquiat sales over $5M in the last 2 years…"
                style={{flex:1, background:MW.white, border:`0.5px solid ${MW.border}`, borderRadius:4, color:MW.bodyText, padding:"8px 12px", fontSize:13, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif"}} />
              <button onClick={() => customRequest.trim() && !fetchPending && requestData(customRequest)}
                disabled={fetchPending} style={fetchPending ? btnDisabled : btnPrimary}>
                {fetchPending ? "Querying…" : "Request →"}
              </button>
            </div>
          )}

          {fetchPending && <div style={{marginTop:12, fontSize:12, color:MW.purple}}>Querying Redshift via Lambda…</div>}
          {fetchError  && <div style={{marginTop:12, padding:"10px 14px", background:"#FFF5F5", border:`0.5px solid ${MW.callout}`, borderRadius:4, fontSize:12, color:MW.callout}}>{fetchError}</div>}
          {!stats && !marketSeries && !fetchPending && !fetchError && (
            <div style={{marginTop:14, padding:"10px 14px", background:MW.rowAlt, borderRadius:4, border:`0.5px solid ${MW.border}`, fontSize:12, color:MW.muted}}>
              Select a preset or enter a request, then click <strong style={{color:MW.purple}}>Request Data →</strong>
            </div>
          )}

          {(rows.length > 0 || marketSeries) && !fetchPending && (
            <div style={{marginTop:12, display:"flex", alignItems:"center", gap:12, flexWrap:"wrap"}}>
              {dataType === "market"
                ? <span style={{fontSize:12, color:MW.green}}>
                    {marketSeries?.tickers?.length > 0
                      ? `${marketSeries.tickers.length} series · ${(marketSeries.series[marketSeries.tickers[0]] || []).length} data points`
                      : "Market data loaded"}
                  </span>
                : <span style={{fontSize:12, color:MW.green}}>{rows.length} lots loaded</span>
              }
              {imageLots.length > 0 && (
                <span style={{fontSize:12, color:MW.purple}}>{imageLots.length} image lots available</span>
              )}
              <div style={{display:"flex", gap:6}}>
                {lastSQL && (
                  <button onClick={() => setShowSQL(s=>!s)} style={{...btnBase, padding:"3px 10px", fontSize:11}}>
                    {showSQL ? "Hide SQL" : "Show SQL"}
                  </button>
                )}
                {lastImgSQL && (
                  <button onClick={() => setShowImgSQL(s=>!s)} style={{...btnBase, padding:"3px 10px", fontSize:11}}>
                    {showImgSQL ? "Hide Image SQL" : "Show Image SQL"}
                  </button>
                )}
              </div>
            </div>
          )}
          {showSQL && lastSQL && (
            <pre style={{marginTop:10, padding:"10px 14px", background:MW.rowAlt, border:`0.5px solid ${MW.border}`, borderRadius:4, fontSize:11, color:MW.subText, overflowX:"auto", whiteSpace:"pre-wrap", fontFamily:"monospace"}}>{lastSQL}</pre>
          )}
          {showImgSQL && lastImgSQL && (
            <pre style={{marginTop:8, padding:"10px 14px", background:MW.purpleBg, border:`0.5px solid ${MW.softLav}`, borderRadius:4, fontSize:11, color:MW.purple, overflowX:"auto", whiteSpace:"pre-wrap", fontFamily:"monospace"}}>{lastImgSQL}</pre>
          )}
        </div>

        {/* Image Strip — clickable thumbnails that drive the carousel */}
        {imageLots.length > 0 && (
          <div style={{...card, padding:"16px 20px"}}>
            <div style={{...eyebrow, marginBottom:12}}>Available Images · {imageLots.length} lots</div>
            <div style={{display:"flex", gap:8, overflowX:"auto", paddingBottom:4}}>
              {imageLots.map((lot, i) => (
                <div key={i} onClick={() => setCarouselIdx(i)}
                  style={{flexShrink:0, width:72, cursor:"pointer", opacity: i === carouselIdx ? 1 : 0.65, transition:"opacity 0.15s", position:"relative"}}>
                  <img src={lot.img} alt={lot.title}
                    style={{width:72, height:72, objectFit:"cover", borderRadius:6, display:"block",
                      border: i === carouselIdx ? `2px solid ${MW.purple}` : `0.5px solid ${MW.border}`}}
                    onError={e => e.target.parentElement.style.display="none"} />
                  <button onClick={e => { e.stopPropagation(); removeImageLot(i); }}
                    style={{position:"absolute", top:3, right:3, background:"rgba(0,0,0,0.5)", border:"none", borderRadius:"50%", width:18, height:18, display:"flex", alignItems:"center", justifyContent:"center", cursor:"pointer", padding:0}}>
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                  <div style={{fontSize:10, color:MW.muted, marginTop:4, lineHeight:1.3, overflow:"hidden", textOverflow:"ellipsis", whiteSpace:"nowrap"}}>{lot.isChart ? lot.title : lot.artist?.split(" ").pop()}</div>
                  {!lot.isChart && <div style={{fontSize:10, color:MW.purple, fontWeight:500}}>{fmt(lot.hammer)}</div>}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Stats Strip — only when the result has standard lot columns and sold data */}
        {stats && columns.includes("hammer_price_usd") && columns.includes("lot_status") && stats.sold.length > 0 && (() => {
          const isSingle   = stats.uniqueArtists?.length === 1;
          const avgSale    = stats.sold.length ? stats.total / stats.sold.length : 0;
          const careerHigh = stats.topLot ? +stats.get(stats.topLot,"hammer_price_usd") : 0;
          const cards = isSingle ? [
            {label:"Works Tracked", value:stats.sold.length},
            {label:"Total Revenue", value:fmt(stats.total)},
            {label:"Avg per Sale",  value:fmt(avgSale)},
            {label:"Career High",   value:fmt(careerHigh)},
          ] : [
            {label:"Total Lots",   value:rows.length},
            {label:"Sell-Through", value:`${stats.sellThru}%`},
            {label:"Total Hammer", value:fmt(stats.total)},
            {label:"Avg Premium",  value:`${stats.avgPremium>0?"+":""}${stats.avgPremium.toFixed(0)}%`},
          ];
          return (
            <div style={{display:"grid", gridTemplateColumns:"repeat(4,1fr)", gap:12, marginBottom:16}}>
              {cards.map(s => (
                <div key={s.label} style={{background:MW.white, border:`0.5px solid ${MW.border}`, borderRadius:8, padding:"14px 16px", textAlign:"center"}}>
                  <div style={{fontSize:22, fontWeight:700, color:MW.purple}}>{s.value}</div>
                  <div style={{fontSize:10, color:MW.muted, letterSpacing:"0.08em", textTransform:"uppercase", marginTop:4}}>{s.label}</div>
                </div>
              ))}
            </div>
          );
        })()}

        {/* Market Stats Strip */}
        {dataType === "market" && marketSeries && (() => {
          let cards;
          if (marketSeries.tickers?.length > 0) {
            // Timeseries: show period return per series
            cards = marketSeries.tickers.map(t => {
              const pts = marketSeries.series[t] || [];
              const first = pts[0]?.value, last = pts[pts.length - 1]?.value;
              const chg = first && last ? (((last - first) / first) * 100).toFixed(1) : null;
              return {
                label: t,
                value: chg !== null ? `${chg > 0 ? "+" : ""}${chg}%` : "N/A",
                positive: chg !== null ? +chg > 0 : null,
              };
            });
          } else if (marketSeries.aggregates?.length > 0) {
            // Aggregate: show each column as a card
            const agg = marketSeries.aggregates[0];
            cards = Object.entries(agg).filter(([,v]) => v !== null).slice(0, 4).map(([k, v]) => ({
              label: k.replace(/_/g, " "),
              value: typeof v === "number" ? (Math.abs(v) < 2 ? v.toFixed(3) : Number(v).toLocaleString()) : String(v),
              positive: typeof v === "number" ? v >= 0 : null,
            }));
          }
          if (!cards?.length) return null;
          return (
            <div style={{display:"grid", gridTemplateColumns:`repeat(${Math.min(cards.length,4)},1fr)`, gap:12, marginBottom:16}}>
              {cards.map(s => (
                <div key={s.label} style={{background:MW.white, border:`0.5px solid ${MW.border}`, borderRadius:8, padding:"14px 16px", textAlign:"center"}}>
                  <div style={{fontSize:22, fontWeight:700, color:s.positive===false ? MW.callout : MW.purple}}>{s.value}</div>
                  <div style={{fontSize:10, color:MW.muted, letterSpacing:"0.08em", textTransform:"uppercase", marginTop:4}}>{s.label}</div>
                </div>
              ))}
            </div>
          );
        })()}

        {/* Market Chart */}
        {(dataType === "market" || dataType === "mixed") && marketSeries && (
          <div ref={marketChartRef} style={card}>
            <div style={{display:"flex", justifyContent:"space-between", alignItems:"center", marginBottom:16}}>
              <div style={eyebrow}>
                {marketSeries.tickers?.length > 1
                  ? `${marketSeries.tickers.join(" vs ")} · Indexed to 100`
                  : marketSeries.tickers?.length === 1
                    ? `${marketSeries.tickers[0]} · Performance`
                    : "Market Summary"}
              </div>
            </div>
            <MarketChart marketSeries={marketSeries} />
            <div style={{marginTop:14, display:"flex", justifyContent:"flex-end"}}>
              <button
                onClick={() => addChartToPost(marketChartRef,
                  marketSeries.tickers?.length > 0 ? `${marketSeries.tickers.join(" vs ")} chart` : "Market chart")}
                style={chartCaptured==="market" ? {...btnBase,background:"#EAF3DE",borderColor:MW.green,color:MW.green} : btnBase}>
                {chartCaptured==="market" ? "Added to post ✓" : "Add chart to post"}
              </button>
            </div>
          </div>
        )}

        {/* Lot Chart — only when the result has the columns these charts depend on */}
        {stats && columns.includes("hammer_price_usd") && columns.includes("lot_status") &&
         (stats.artistChart.length > 0 || stats.premiumChart.length > 0 || stats.timeseriesData?.length > 0) && (
          <div ref={lotChartRef} style={card}>
            <div style={{display:"flex", justifyContent:"space-between", alignItems:"center", marginBottom:16}}>
              <div style={eyebrow}>
                {chartType==="timeseries"
                  ? (stats.uniqueArtists?.length===1 ? `${stats.uniqueArtists[0]} · Sales Timeline` : "Sales Timeline")
                  : chartType==="revenue" ? "Revenue by Artist" : "Premium vs. Estimate (%)"}
              </div>
              <div style={{display:"flex", gap:6}}>
                {[
                  ["revenue","By Artist"],
                  ["premium","Premium %"],
                  ...(stats.timeseriesData?.length > 0 ? [["timeseries","Timeline"]] : []),
                ].map(([k,l]) => (
                  <button key={k} onClick={() => setChartType(k)}
                    style={chartType===k ? {...btnActive,padding:"4px 10px",fontSize:11} : {...btnBase,padding:"4px 10px",fontSize:11}}>{l}</button>
                ))}
              </div>
            </div>
            <ResponsiveContainer width="100%" height={200}>
              {chartType==="timeseries" ? (
                <BarChart data={stats.timeseriesData} margin={{left:0,right:0,top:0,bottom:0}}>
                  <XAxis dataKey="displayDate" tick={{fill:MW.muted,fontSize:9}} axisLine={false} tickLine={false}
                    interval={Math.max(0, Math.floor(stats.timeseriesData.length/6)-1)}/>
                  <YAxis tickFormatter={fmt} tick={{fill:MW.muted,fontSize:10}} axisLine={false} tickLine={false} width={60}/>
                  <Tooltip
                    formatter={v=>[fmt(v),"Hammer"]}
                    contentStyle={{background:MW.white,border:`0.5px solid ${MW.border}`,borderRadius:6,color:MW.bodyText,fontSize:12}}
                    labelFormatter={(_,p)=>p?.[0]?.payload ? `${p[0].payload.title} · ${p[0].payload.date}` : ""}/>
                  <Bar dataKey="hammer" radius={[3,3,0,0]}>
                    {stats.timeseriesData.map((_,i)=><Cell key={i} fill={CHART_COLORS[i%CHART_COLORS.length]}/>)}
                  </Bar>
                </BarChart>
              ) : chartType==="revenue" ? (
                <BarChart data={stats.artistChart} margin={{left:0,right:0,top:0,bottom:0}}>
                  <XAxis dataKey="name" tick={{fill:MW.muted,fontSize:10}} axisLine={false} tickLine={false}/>
                  <YAxis tickFormatter={fmt} tick={{fill:MW.muted,fontSize:10}} axisLine={false} tickLine={false} width={60}/>
                  <Tooltip formatter={v=>[fmt(v),"Hammer"]} contentStyle={{background:MW.white,border:`0.5px solid ${MW.border}`,borderRadius:6,color:MW.bodyText,fontSize:12}} labelFormatter={(_,p)=>p?.[0]?.payload?.full||""}/>
                  <Bar dataKey="total" radius={[3,3,0,0]}>{stats.artistChart.map((_,i)=><Cell key={i} fill={CHART_COLORS[i%CHART_COLORS.length]}/>)}</Bar>
                </BarChart>
              ) : (
                <BarChart data={stats.premiumChart} margin={{left:0,right:0,top:0,bottom:0}}>
                  <XAxis dataKey="name" tick={{fill:MW.muted,fontSize:10}} axisLine={false} tickLine={false}/>
                  <YAxis tickFormatter={v=>`${v}%`} tick={{fill:MW.muted,fontSize:10}} axisLine={false} tickLine={false} width={50}/>
                  <ReferenceLine y={0} stroke={MW.border}/>
                  <Tooltip formatter={v=>[`${v}%`,"Premium"]} contentStyle={{background:MW.white,border:`0.5px solid ${MW.border}`,borderRadius:6,color:MW.bodyText,fontSize:12}} labelFormatter={(_,p)=>p?.[0]?.payload?.title||""}/>
                  <Bar dataKey="premium" radius={[3,3,0,0]}>{stats.premiumChart.map((e,i)=><Cell key={i} fill={e.premium>=0?MW.purple:MW.callout}/>)}</Bar>
                </BarChart>
              )}
            </ResponsiveContainer>
            <div style={{marginTop:14, display:"flex", justifyContent:"flex-end"}}>
              <button
                onClick={() => addChartToPost(lotChartRef,
                  chartType==="timeseries" ? "Sales Timeline chart"
                  : chartType==="revenue"   ? "Revenue by Artist chart"
                  :                           "Premium vs. Estimate chart")}
                style={chartCaptured==="lot" ? {...btnBase,background:"#EAF3DE",borderColor:MW.green,color:MW.green} : btnBase}>
                {chartCaptured==="lot" ? "Added to post ✓" : "Add chart to post"}
              </button>
            </div>
          </div>
        )}

        {/* Top Lot — prefer image sidecar for the hero image */}
        {stats?.topLot && (() => {
          const get = stats.get;
          const heroImg = imageLots[0]?.img || get(stats.topLot,"internal_image_url");
          return (
            <div style={{...card, display:"flex", gap:16, alignItems:"center"}}>
              {heroImg && (
                <img src={heroImg} alt="top lot"
                  style={{height:80, width:80, objectFit:"cover", borderRadius:6, border:`0.5px solid ${MW.border}`, flexShrink:0}}
                  onError={e=>e.target.style.display="none"}/>
              )}
              <div>
                <div style={{...eyebrow, marginBottom:4}}>Top Lot</div>
                <div style={{fontSize:15, color:MW.titleText, fontWeight:500}}>{get(stats.topLot,"work_title")}</div>
                <div style={{fontSize:12, color:MW.muted, marginTop:2}}>
                  {get(stats.topLot,"artist_name")} · {get(stats.topLot,"auction_house")} · {get(stats.topLot,"start_date")?.slice(0,10)}
                </div>
                <div style={{fontSize:18, color:MW.purple, fontWeight:700, marginTop:4}}>{fmt(+get(stats.topLot,"hammer_price_usd"))}</div>
              </div>
            </div>
          );
        })()}

        {/* Post Generator */}
        <div style={card}>
          <div style={eyebrow}>Generate Post</div>
          {!stats && !marketSeries && <div style={{fontSize:12, color:MW.muted, marginBottom:12}}>Load data above first to enable post generation.</div>}
          <textarea value={prompt} onChange={e=>setPrompt(e.target.value)} disabled={!stats && !marketSeries}
            placeholder='Describe your post… e.g. "Highlight the biggest premiums and what it signals for collector demand"'
            style={{width:"100%",height:80,background:(stats||marketSeries)?MW.white:MW.rowAlt,border:`0.5px solid ${MW.border}`,borderRadius:4,color:(stats||marketSeries)?MW.bodyText:MW.muted,padding:12,fontSize:13,fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif",resize:"none",boxSizing:"border-box",lineHeight:1.6}}/>
          <div style={{display:"flex", gap:16, marginTop:14, flexWrap:"wrap", alignItems:"flex-end"}}>
            <div style={{flex:1, minWidth:160}}>
              <div style={{fontSize:10,color:MW.muted,letterSpacing:"0.08em",textTransform:"uppercase",marginBottom:6}}>Platform</div>
              <div style={{display:"flex",gap:4}}>
                {PLATFORMS.map(p=><button key={p} onClick={()=>setPlatform(p)} style={{...(platform===p?btnActive:btnBase),flex:1,padding:"6px 4px"}}>{p}</button>)}
              </div>
            </div>
            <div style={{flex:1, minWidth:220}}>
              <div style={{fontSize:10,color:MW.muted,letterSpacing:"0.08em",textTransform:"uppercase",marginBottom:6}}>Tone</div>
              <div style={{display:"flex",gap:4}}>
                {TONES.map(t=><button key={t} onClick={()=>setTone(t)} style={{...(tone===t?btnActive:btnBase),flex:1,padding:"6px 4px"}}>{t}</button>)}
              </div>
            </div>
            <button onClick={generate} disabled={genLoading||!prompt.trim()||(!stats&&!marketSeries)}
              style={genLoading||!prompt.trim()||(!stats&&!marketSeries) ? btnDisabled : btnPrimary}>
              {genLoading ? "Generating…" : "Generate →"}
            </button>
          </div>
        </div>

        {/* Result */}
        {result && !result.error && (
          <div style={card}>
            <div style={eyebrow}>Generated Post</div>
            {result.headline && (
              <div style={{fontFamily:"'Playfair Display','DM Serif Display',Georgia,serif",fontSize:20,fontWeight:900,color:MW.titleText,marginBottom:14,lineHeight:1.3}}>
                {result.headline}
              </div>
            )}
            {result.keyStats && (
              <div style={{display:"flex",gap:8,marginBottom:16,flexWrap:"wrap"}}>
                {result.keyStats.map((s,i)=>(
                  <div key={i} style={{padding:"4px 12px",background:MW.purpleBg,border:`0.5px solid ${MW.softLav}`,borderRadius:20,fontSize:12,color:MW.purple}}>{s}</div>
                ))}
              </div>
            )}
            <div style={{display:"flex",gap:8,marginBottom:16}}>
              {["linkedin","instagram"].map(p=>result[p]?.length>0&&(
                <button key={p} onClick={()=>{ setTab(p); setEditing(false); }} style={tab===p?{...btnActive,padding:"6px 18px"}:{...btnBase,padding:"6px 18px"}}>
                  {p==="linkedin"?"LinkedIn":"Instagram"}
                </button>
              ))}
            </div>

            {/* Instagram mockup */}
            {tab==="instagram" && result.instagram && (() => {
              const igText = editedInstagram ?? result.instagram;
              return (
                <div>
                  <InstagramPost post={igText} images={imageLots} activeIndex={carouselIdx} onIndexChange={setCarouselIdx} onRemove={() => removeImageLot(carouselIdx)}/>
                  {editing && (
                    <div style={{marginTop:12}}>
                      <textarea
                        value={igText}
                        onChange={e => setEditedInstagram(e.target.value)}
                        style={{width:"100%", boxSizing:"border-box", height:160, background:MW.white, border:`1px solid ${MW.purple}`, borderRadius:6, padding:"12px 14px", fontSize:13, lineHeight:1.7, color:MW.bodyText, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif", resize:"vertical", outline:"none"}}
                      />
                    </div>
                  )}
                  <div style={{marginTop:10, display:"flex", justifyContent:"center", gap:8}}>
                    <button onClick={()=>setEditing(e=>!e)}
                      style={editing ? {...btnActive,padding:"7px 18px"} : {...btnBase,padding:"7px 18px"}}>
                      {editing ? "Done editing" : "Edit caption"}
                    </button>
                    <button onClick={()=>copy(igText,"instagram")}
                      style={copied==="instagram"?{...btnBase,background:"#EAF3DE",borderColor:MW.green,color:MW.green,padding:"7px 20px"}:{...btnBase,padding:"7px 20px"}}>
                      {copied==="instagram"?"Copied":"Copy caption"}
                    </button>
                  </div>
                </div>
              );
            })()}

            {/* LinkedIn plain text */}
            {tab==="linkedin" && result.linkedin && (() => {
              const liText = editedLinkedIn ?? result.linkedin;
              return (
                <div>
                  <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",marginBottom:8,paddingBottom:8,borderBottom:`0.5px solid ${MW.border}`}}>
                    <div style={{display:"flex",alignItems:"center",gap:10}}>
                      <div style={{width:3,height:16,background:MW.purple}}/>
                      <span style={{fontSize:11,color:MW.purple,fontWeight:500,letterSpacing:"0.06em",textTransform:"uppercase"}}>LinkedIn</span>
                      {editedLinkedIn !== null && <span style={{fontSize:10,color:MW.purple,background:MW.purpleBg,border:`0.5px solid ${MW.softLav}`,padding:"2px 8px",borderRadius:10}}>edited</span>}
                    </div>
                    <button onClick={()=>setEditing(e=>!e)}
                      style={editing ? {...btnActive,padding:"4px 14px",fontSize:12} : {...btnBase,padding:"4px 14px",fontSize:12}}>
                      {editing ? "Done editing" : "Edit"}
                    </button>
                  </div>
                  {editing ? (
                    <textarea
                      value={liText}
                      onChange={e => setEditedLinkedIn(e.target.value)}
                      style={{width:"100%", boxSizing:"border-box", height:240, background:MW.white, border:`1px solid ${MW.purple}`, borderRadius:6, padding:"14px 16px", fontSize:14, lineHeight:1.8, color:MW.bodyText, fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif", resize:"vertical", outline:"none", marginBottom:12}}
                    />
                  ) : (
                    <div style={{background:MW.rowAlt,border:`0.5px solid ${MW.border}`,borderRadius:6,padding:"16px 18px",fontSize:14,lineHeight:1.8,color:MW.bodyText,whiteSpace:"pre-wrap",marginBottom:12,fontFamily:"Helvetica Neue, Helvetica, Arial, sans-serif"}}>
                      {liText}
                    </div>
                  )}
                  <button onClick={()=>copy(liText,"linkedin")}
                    style={copied==="linkedin"?{...btnBase,background:"#EAF3DE",borderColor:MW.green,color:MW.green,padding:"7px 20px"}:{...btnBase,padding:"7px 20px"}}>
                    {copied==="linkedin"?"Copied":"Copy to clipboard"}
                  </button>
                </div>
              );
            })()}

            {/* Approval + Save */}
            <div style={{marginTop:20, paddingTop:16, borderTop:`0.5px solid ${MW.border}`}}>
              <div style={{...eyebrow, marginBottom:12}}>Save to Google Drive</div>
              <div style={{display:"flex", gap:20, marginBottom:14}}>
                {[["approved","Approved","#2D7A16","#EAF3DE"],["needs_review","Needs Review",MW.callout,"#FFF5F5"]].map(([val, label, color, bg]) => (
                  <label key={val} style={{display:"flex", alignItems:"center", gap:8, cursor:"pointer",
                    padding:"8px 14px", borderRadius:6, border:`1px solid ${approvalStatus===val ? color : MW.border}`,
                    background: approvalStatus===val ? bg : MW.white, transition:"all 0.15s"}}>
                    <input type="checkbox" checked={approvalStatus===val}
                      onChange={() => { setApprovalStatus(approvalStatus===val ? null : val); setSaveState("idle"); setSaveUrl(null); }}
                      style={{accentColor: color, width:14, height:14, cursor:"pointer"}}/>
                    <span style={{fontSize:13, fontWeight:500, color: approvalStatus===val ? color : MW.bodyText}}>{label}</span>
                  </label>
                ))}
              </div>
              <div style={{display:"flex", alignItems:"center", gap:12}}>
                <button onClick={saveToDrive} disabled={!approvalStatus || saveState==="saving"}
                  style={!approvalStatus || saveState==="saving" ? btnDisabled : btnPrimary}>
                  {saveState==="saving" ? "Saving…" : "Save to Drive →"}
                </button>
                {saveState==="saved" && (
                  <span style={{fontSize:12, color:MW.green}}>
                    Saved
                    {saveUrl && <> · <a href={saveUrl} target="_blank" rel="noreferrer" style={{color:MW.green}}>Open in Drive</a></>}
                  </span>
                )}
                {saveState==="error" && (
                  <span style={{fontSize:12, color:MW.callout}}>Save failed — check Drive credentials</span>
                )}
              </div>
            </div>
          </div>
        )}

        {result?.error && (
          <div style={{...card,background:"#FFF5F5",border:`0.5px solid ${MW.callout}`,color:MW.callout,fontSize:13}}>{result.error}</div>
        )}

        <div style={{textAlign:"center",marginTop:32,paddingTop:20,borderTop:`0.5px solid ${MW.border}`}}>
          <div style={{fontSize:10,letterSpacing:"0.12em",textTransform:"uppercase",color:MW.muted}}>Masterworks · Art Market Intelligence</div>
        </div>

      </div>
    </div>
  );
}