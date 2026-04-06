#!/usr/bin/env python3
"""
Generate a professionally designed PDF for the Masterworks Academy provenance article.
Inspired by the Masterworks institutional investor book design.
"""

from reportlab.lib.pagesizes import letter
from reportlab.lib.units import inch
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak,
    Table, TableStyle, KeepTogether
)
from reportlab.platypus.flowables import Flowable
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_RIGHT, TA_JUSTIFY
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
import textwrap
import math

# ── Colors ──────────────────────────────────────────────────────────────────
ACCENT       = HexColor('#495DE5')
PRIMARY_TEXT = HexColor('#1A1A1A')
SECONDARY    = HexColor('#6B6B6B')
MUTED        = HexColor('#999999')
DARK_BG      = HexColor('#131217')
WHITE        = HexColor('#FFFFFF')
LIGHT_GRAY   = HexColor('#E8E8E8')
RULE_GRAY    = HexColor('#D0D0D0')
GREEN        = HexColor('#24CB71')

PAGE_W, PAGE_H = letter
MARGIN = 1 * inch
CONTENT_W = PAGE_W - 2 * MARGIN


# ── Custom Flowables ────────────────────────────────────────────────────────

class AccentRule(Flowable):
    """Thin colored rule used above section headers."""
    def __init__(self, width, color=ACCENT, thickness=2.5):
        Flowable.__init__(self)
        self.width = width
        self.color = color
        self.thickness = thickness
        self.height = thickness + 2

    def draw(self):
        self.canv.setStrokeColor(self.color)
        self.canv.setLineWidth(self.thickness)
        self.canv.line(0, 0, self.width, 0)

    def wrap(self, availWidth, availHeight):
        return (self.width, self.height)


class ThinRule(Flowable):
    """Thin gray rule for visual separation."""
    def __init__(self, width, color=RULE_GRAY, thickness=0.5):
        Flowable.__init__(self)
        self.width = width
        self.color = color
        self.thickness = thickness
        self.height = thickness + 8

    def draw(self):
        self.canv.setStrokeColor(self.color)
        self.canv.setLineWidth(self.thickness)
        self.canv.line(0, 4, self.width, 4)

    def wrap(self, availWidth, availHeight):
        return (self.width, self.height)


class HorizontalBarChart(Flowable):
    """Horizontal bar chart drawn inline with reportlab."""
    def __init__(self, title, data, source_text, width=None, bar_height=32, chart_height=None):
        Flowable.__init__(self)
        self.title = title
        self.data = data  # list of (label, value, color)
        self.source_text = source_text
        self.chart_width = width or CONTENT_W
        self.bar_height = bar_height
        self.bar_gap = 14
        n = len(data)
        self.total_height = 36 + n * (self.bar_height + self.bar_gap) + 30
        self.height = self.total_height

    def wrap(self, availWidth, availHeight):
        return (self.chart_width, self.total_height)

    def draw(self):
        c = self.canv
        w = self.chart_width
        h = self.total_height

        # Background
        c.setFillColor(DARK_BG)
        c.roundRect(0, 0, w, h, 6, fill=1, stroke=0)

        # Title
        c.setFillColor(WHITE)
        c.setFont('Helvetica-Bold', 13)
        c.drawString(20, h - 26, self.title)

        # Bars
        max_val = max(d[1] for d in self.data)
        label_area = 110
        bar_area = w - label_area - 80
        y = h - 50

        for label, value, color in self.data:
            # Label
            c.setFillColor(LIGHT_GRAY)
            c.setFont('Helvetica', 10)
            c.drawString(20, y - self.bar_height / 2 + 3, label)

            # Bar
            bar_w = (value / max_val) * bar_area
            c.setFillColor(color)
            c.roundRect(label_area, y - self.bar_height + 4, bar_w, self.bar_height - 6, 3, fill=1, stroke=0)

            # Value label
            c.setFillColor(WHITE)
            c.setFont('Helvetica-Bold', 11)
            if isinstance(value, float):
                val_str = f"+{value:.0f}%"
            elif isinstance(value, int) and value <= 100:
                val_str = f"{value}%"
            else:
                val_str = str(value)
            c.drawString(label_area + bar_w + 8, y - self.bar_height / 2 + 3, val_str)

            y -= (self.bar_height + self.bar_gap)

        # Source
        c.setFillColor(MUTED)
        c.setFont('Helvetica', 8)
        c.drawString(20, 10, self.source_text)


class CalloutBox(Flowable):
    """Dark background callout box with key stat."""
    def __init__(self, headline, stat, stat_label, details, width=None):
        Flowable.__init__(self)
        self.headline = headline
        self.stat = stat
        self.stat_label = stat_label
        self.details = details
        self.box_width = width or CONTENT_W
        self.height = 140

    def wrap(self, availWidth, availHeight):
        return (self.box_width, self.height)

    def draw(self):
        c = self.canv
        w = self.box_width
        h = self.height

        # Background
        c.setFillColor(DARK_BG)
        c.roundRect(0, 0, w, h, 6, fill=1, stroke=0)

        # Headline
        c.setFillColor(MUTED)
        c.setFont('Helvetica', 9)
        c.drawString(24, h - 24, self.headline.upper())

        # Big stat
        c.setFillColor(ACCENT)
        c.setFont('Helvetica-Bold', 40)
        c.drawString(24, h - 72, self.stat)

        # Stat label
        c.setFillColor(WHITE)
        c.setFont('Helvetica', 12)
        c.drawString(24, h - 92, self.stat_label)

        # Details on the right
        x_right = w / 2 + 20
        y_detail = h - 40
        c.setFont('Helvetica', 10)
        for line in self.details:
            c.setFillColor(LIGHT_GRAY)
            c.drawString(x_right, y_detail, line)
            y_detail -= 18


class PullQuoteBox(Flowable):
    """Pull quote with accent bar on left."""
    def __init__(self, text, width=None):
        Flowable.__init__(self)
        self.text = text
        self.box_width = width or CONTENT_W
        # Estimate height
        chars_per_line = int((self.box_width - 40) / 7.5)
        lines = math.ceil(len(text) / chars_per_line) + 1
        self.height = max(60, lines * 20 + 24)

    def wrap(self, availWidth, availHeight):
        return (self.box_width, self.height)

    def draw(self):
        c = self.canv
        h = self.height
        w = self.box_width

        # Left accent bar
        c.setFillColor(ACCENT)
        c.rect(0, 0, 4, h, fill=1, stroke=0)

        # Quote text
        c.setFillColor(PRIMARY_TEXT)
        c.setFont('Helvetica-Bold', 13)

        # Word wrap
        chars_per_line = int((w - 40) / 7.5)
        wrapped = textwrap.wrap(self.text, width=chars_per_line)
        y = h - 20
        for line in wrapped:
            c.drawString(20, y, line)
            y -= 19


class VerticalBarChart(Flowable):
    """Vertical bar chart for rarity of documentation."""
    def __init__(self, title, data, source_text, width=None):
        Flowable.__init__(self)
        self.title = title
        self.data = data  # list of (label, value, color)
        self.source_text = source_text
        self.chart_width = width or CONTENT_W
        self.height = 260

    def wrap(self, availWidth, availHeight):
        return (self.chart_width, self.height)

    def draw(self):
        c = self.canv
        w = self.chart_width
        h = self.height

        # Background
        c.setFillColor(DARK_BG)
        c.roundRect(0, 0, w, h, 6, fill=1, stroke=0)

        # Title
        c.setFillColor(WHITE)
        c.setFont('Helvetica-Bold', 13)
        c.drawString(20, h - 26, self.title)

        # Chart area
        chart_bottom = 44
        chart_top = h - 50
        chart_height = chart_top - chart_bottom
        max_val = max(d[1] for d in self.data)

        n = len(self.data)
        total_bar_area = w - 80
        bar_width = total_bar_area / (n * 2)
        gap = bar_width

        # Grid lines
        c.setStrokeColor(HexColor('#13134A'))
        c.setLineWidth(0.5)
        for pct in [0.25, 0.5, 0.75, 1.0]:
            y = chart_bottom + chart_height * pct
            c.line(40, y, w - 20, y)
            c.setFillColor(MUTED)
            c.setFont('Helvetica', 8)
            c.drawRightString(38, y - 3, f"{int(max_val * pct)}%")

        # Bars
        x = 60
        for label, value, color in self.data:
            bar_h = (value / max_val) * chart_height
            c.setFillColor(color)
            c.roundRect(x, chart_bottom, bar_width, bar_h, 3, fill=1, stroke=0)

            # Value on top
            c.setFillColor(WHITE)
            c.setFont('Helvetica-Bold', 11)
            c.drawCentredString(x + bar_width / 2, chart_bottom + bar_h + 6, f"{value}%")

            # Label below
            c.setFillColor(LIGHT_GRAY)
            c.setFont('Helvetica', 9)
            c.drawCentredString(x + bar_width / 2, chart_bottom - 16, label)

            x += bar_width + gap

        # Source
        c.setFillColor(MUTED)
        c.setFont('Helvetica', 8)
        c.drawString(20, 10, self.source_text)


# ── Page Template with header/footer ───────────────────────────────────────

def footer_template(canvas_obj, doc):
    """Draw footer on every page (except title page handled separately)."""
    canvas_obj.saveState()
    page_num = doc.page

    if page_num > 1:
        # Thin rule
        canvas_obj.setStrokeColor(RULE_GRAY)
        canvas_obj.setLineWidth(0.5)
        canvas_obj.line(MARGIN, 0.65 * inch, PAGE_W - MARGIN, 0.65 * inch)

        # Left: brand
        canvas_obj.setFillColor(MUTED)
        canvas_obj.setFont('Helvetica', 8)
        canvas_obj.drawString(MARGIN, 0.45 * inch, 'Masterworks Academy')

        # Right: page number
        canvas_obj.drawRightString(PAGE_W - MARGIN, 0.45 * inch, str(page_num))

    canvas_obj.restoreState()


# ── Styles ──────────────────────────────────────────────────────────────────

style_body = ParagraphStyle(
    'Body',
    fontName='Helvetica',
    fontSize=11,
    leading=16,
    textColor=PRIMARY_TEXT,
    alignment=TA_JUSTIFY,
    spaceAfter=10,
)

style_h2 = ParagraphStyle(
    'H2',
    fontName='Helvetica-Bold',
    fontSize=16,
    leading=22,
    textColor=PRIMARY_TEXT,
    spaceAfter=8,
    spaceBefore=4,
)

style_h3 = ParagraphStyle(
    'H3',
    fontName='Helvetica-Bold',
    fontSize=13,
    leading=18,
    textColor=PRIMARY_TEXT,
    spaceAfter=6,
    spaceBefore=10,
)

style_bullet = ParagraphStyle(
    'Bullet',
    fontName='Helvetica',
    fontSize=11,
    leading=16,
    textColor=PRIMARY_TEXT,
    alignment=TA_LEFT,
    leftIndent=18,
    bulletIndent=0,
    spaceAfter=6,
)

style_caption = ParagraphStyle(
    'Caption',
    fontName='Helvetica',
    fontSize=9,
    leading=12,
    textColor=MUTED,
    spaceAfter=4,
)

style_source = ParagraphStyle(
    'Source',
    fontName='Helvetica',
    fontSize=8,
    leading=11,
    textColor=MUTED,
    spaceAfter=3,
)

style_disclosure = ParagraphStyle(
    'Disclosure',
    fontName='Helvetica',
    fontSize=9,
    leading=14,
    textColor=SECONDARY,
    alignment=TA_LEFT,
    spaceAfter=6,
)

style_faq_q = ParagraphStyle(
    'FAQQ',
    fontName='Helvetica-Bold',
    fontSize=12,
    leading=17,
    textColor=PRIMARY_TEXT,
    spaceAfter=4,
    spaceBefore=14,
)

style_faq_a = ParagraphStyle(
    'FAQA',
    fontName='Helvetica',
    fontSize=11,
    leading=16,
    textColor=PRIMARY_TEXT,
    alignment=TA_JUSTIFY,
    spaceAfter=10,
)

style_bottom_line_bullet = ParagraphStyle(
    'BottomLineBullet',
    fontName='Helvetica',
    fontSize=11,
    leading=16,
    textColor=PRIMARY_TEXT,
    alignment=TA_LEFT,
    leftIndent=18,
    bulletIndent=0,
    spaceAfter=8,
)


# ── Title Page Flowable ────────────────────────────────────────────────────

class TitlePage(Flowable):
    """Full-page title page."""
    def __init__(self):
        Flowable.__init__(self)
        self.width = CONTENT_W
        self.height = PAGE_H - 2 * MARGIN

    def wrap(self, availWidth, availHeight):
        self.width = min(self.width, availWidth)
        self.height = min(self.height, availHeight)
        return (self.width, self.height)

    def draw(self):
        c = self.canv
        h = self.height
        w = self.width

        # Accent bar at top
        c.setFillColor(ACCENT)
        c.rect(0, h - 6, w, 6, fill=1, stroke=0)

        # Category label
        c.setFillColor(ACCENT)
        c.setFont('Helvetica-Bold', 10)
        c.drawString(0, h - 50, 'OPINIONS & EXPLAINERS')

        # Title
        c.setFillColor(PRIMARY_TEXT)
        c.setFont('Helvetica-Bold', 28)
        title_lines = [
            "Understanding Provenance:",
            "Why an Artwork's History",
            "Drives Its Value"
        ]
        y = h - 100
        for line in title_lines:
            c.drawString(0, y, line)
            y -= 38

        # Subtitle / meta description
        c.setFillColor(SECONDARY)
        c.setFont('Helvetica', 12)
        subtitle_lines = textwrap.wrap(
            "Provenance, the ownership history of an artwork, can add up to 54% "
            "to hammer prices at auction. Learn how investors evaluate provenance "
            "and spot red flags.",
            width=72
        )
        y -= 20
        for line in subtitle_lines:
            c.drawString(0, y, line)
            y -= 18

        # Divider
        y -= 30
        c.setStrokeColor(RULE_GRAY)
        c.setLineWidth(0.5)
        c.line(0, y, w * 0.3, y)

        # Branding at bottom
        c.setFillColor(PRIMARY_TEXT)
        c.setFont('Helvetica-Bold', 14)
        c.drawString(0, 60, 'Masterworks Academy')

        c.setFillColor(MUTED)
        c.setFont('Helvetica', 9)
        c.drawString(0, 42, 'masterworks.com/academy')

        # Accent dot
        c.setFillColor(ACCENT)
        c.circle(w - 20, 55, 8, fill=1, stroke=0)


# ── Build the Document ──────────────────────────────────────────────────────

def build_pdf():
    output_path = '/Users/jacknorman/Desktop/claudeprojects/academy/content/enriched/understanding-provenance-designed.pdf'

    doc = SimpleDocTemplate(
        output_path,
        pagesize=letter,
        leftMargin=MARGIN,
        rightMargin=MARGIN,
        topMargin=MARGIN,
        bottomMargin=0.85 * inch,
    )

    story = []

    # ── Title Page ──
    story.append(TitlePage())
    story.append(PageBreak())

    # ── Introduction ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('Introduction', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'Provenance is the documented chain of ownership for a work of art, tracing who bought, sold, '
        'inherited, or donated it from the time it left the artist\'s studio to the present day. For investors, '
        'provenance is one of the strongest price signals in the market: a 2022 study published in '
        '<i>Management Science</i> found that works with strong provenance records sell for hammer price '
        'premiums of up to 54%, while also boosting annualized returns by 5% to 16%. Gaps in that chain, '
        'by contrast, can crater a work\'s value, expose a buyer to legal claims, or mark a forgery. This '
        'article covers how provenance affects pricing, what verification looks like in practice, and what to '
        'check before you buy.',
        style_body
    ))
    story.append(Spacer(1, 8))

    # ── Pull Quote ──
    story.append(PullQuoteBox(
        "Works with strong provenance records sell for hammer price premiums of up to 54%, "
        "while also boosting annualized returns by 5% to 16%."
    ))
    story.append(Spacer(1, 6))
    story.append(Paragraph(
        'Source: Pownall &amp; Graddy, "In Art We Trust," Management Science, 2022',
        style_caption
    ))
    story.append(Spacer(1, 16))

    # ── Section: What Provenance Actually Includes ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('What Provenance Actually Includes', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'A provenance record is more than a list of former owners. It can include sales receipts, auction '
        'records, exhibition catalogs, gallery invoices, published references in catalogues raisonne, '
        'appraisals, photographs, and correspondence. Each piece of paper ties the physical object to a '
        'specific person at a specific time.',
        style_body
    ))

    story.append(Paragraph(
        'The academic literature breaks provenance into four dimensions, each of which affects price '
        'independently. Pedigree (who owned it and when it last sold) adds roughly 21% to hammer prices. '
        'Exhibition history (whether the work appeared in museum or gallery shows) adds about 42%. '
        'References in published art literature add 54%. And a certificate of authentication adds about 14%. '
        'Those figures come from an analysis of nearly three million auction transactions, published in '
        '<i>European Financial Management</i> in 2022.',
        style_body
    ))

    story.append(Paragraph(
        'Not every work carries all four. In that same dataset, only about 14% of paintings came with '
        'pedigree records, 5% had exhibition histories, 5% appeared in art literature, and just 2% included '
        'certificates. The rarity of complete documentation is part of why it commands such a premium.',
        style_body
    ))
    story.append(Spacer(1, 16))

    # ── Chart 1: Provenance Premium by Type ──
    story.append(HorizontalBarChart(
        title='Provenance Premium by Documentation Type',
        data=[
            ('Literature', 54, ACCENT),
            ('Exhibition', 42, ACCENT),
            ('Pedigree', 21, HexColor('#495DE5CC')),
            ('Certificate', 14, HexColor('#495DE599')),
        ],
        source_text='Source: Li et al., European Financial Management, 2022 (n = ~3M transactions)',
        bar_height=30,
    ))
    story.append(Spacer(1, 20))

    # ── Chart 2: Rarity of Documentation ──
    story.append(VerticalBarChart(
        title='Rarity of Documentation at Auction',
        data=[
            ('Pedigree', 14, ACCENT),
            ('Exhibition', 5, ACCENT),
            ('Literature', 5, HexColor('#495DE5CC')),
            ('Certificate', 2, HexColor('#495DE599')),
        ],
        source_text='Source: Li et al., European Financial Management, 2022',
    ))
    story.append(Spacer(1, 10))

    story.append(PageBreak())

    # ── Section: The Celebrity Effect ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('The Celebrity Effect', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'When a famous collector\'s name is attached to a sale, prices jump. The Paul Allen collection at '
        'Christie\'s in November 2022 brought $1.5 billion across 60 lots, making it the highest-grossing '
        'single-owner auction in history. George Seurat\'s <i>Les Poseuses, Ensemble</i> sold for $149 million. '
        'Paul Cezanne\'s <i>La Montagne Sainte-Victoire</i> hit $137.7 million with fees. Every lot found a buyer.',
        style_body
    ))

    story.append(Paragraph(
        'The Macklowe collection at Sotheby\'s in 2021 and 2022 totaled $922.2 million, with 80% of the works '
        'appearing at auction for the first time. Mark Rothko\'s <i>No. 7</i> (1951) sold for $82.5 million.',
        style_body
    ))

    story.append(Paragraph(
        'In March 2024, The Pattie Boyd Collection at Christie\'s London brought over 2.8 million pounds, '
        'more than seven times its high estimate, with every lot sold. Celebrity provenance turns a painting '
        'into an artifact of someone\'s life, and collectors pay for that story.',
        style_body
    ))

    story.append(Paragraph(
        'Single-owner sales now account for almost a third of major auction volume. Auction houses know that '
        'a recognizable name on a catalog cover pulls bidders who might otherwise sit out.',
        style_body
    ))
    story.append(Spacer(1, 16))

    # ── Callout Box: Paul Allen Collection ──
    story.append(CalloutBox(
        headline='Case Study: The Paul Allen Collection',
        stat='$1.5B',
        stat_label='Total hammer across 60 lots at Christie\'s, November 2022',
        details=[
            '60 lots offered, 60 lots sold (100% sell-through)',
            'Highest-grossing single-owner auction in history',
            'Top lot: Seurat, Les Poseuses, $149M',
            'Cezanne, La Montagne Sainte-Victoire, $137.7M',
        ]
    ))
    story.append(Spacer(1, 6))
    story.append(Paragraph(
        'Source: Artnet News, November 2022',
        style_caption
    ))
    story.append(Spacer(1, 16))

    # ── Section: What Happens When Provenance Breaks Down ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('What Happens When Provenance Breaks Down', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'A gap in ownership records, especially during the 1930s and 1940s, raises immediate questions. '
        'Was the work looted? Was it sold under duress? Is there a living heir with a legal claim?',
        style_body
    ))

    story.append(Paragraph(
        'These are not hypothetical risks. Germany established a new arbitration court for Nazi-looted '
        'property in October 2024, replacing the old Advisory Commission with a body that can issue binding '
        'decisions. France passed a law in February 2024 that, for the first time, allows national institutions '
        'to return looted works, overriding the long-standing rule against deaccessioning public collections. '
        'The U.S. government released guidelines in March 2024 calling on institutions to set up contact points '
        'for restitution claims.',
        style_body
    ))

    story.append(Paragraph(
        'Recent cases show the stakes. Heirs of German-Jewish banker Paul von Mendelssohn-Bartholdy sought '
        'the return of a Van Gogh <i>Sunflowers</i> painting, arguing it was sold under duress in the 1930s. '
        'A U.S. appeals court upheld the dismissal in 2025, ruling it lacked jurisdiction over the Japanese owner. '
        'Heirs of Hungarian collector Baron Mor Lipot Herzog lost their long-running U.S. lawsuit seeking works '
        'held in Hungarian state museums. These cases take years and millions in legal fees, and the outcomes are '
        'far from certain for either side.',
        style_body
    ))

    story.append(Paragraph(
        'For an investor, the financial risk is direct. If a work you own turns out to have a looted-art claim '
        'attached to it, you may face a restitution demand, a lawsuit, or at minimum a work that no auction house '
        'will touch until the dispute resolves. Even the shadow of a claim can knock 10% to 30% off a work\'s '
        'market value, because buyers price in the litigation risk.',
        style_body
    ))

    story.append(PageBreak())

    # ── Section: Forgery and Fabricated Provenance ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('Forgery and Fabricated Provenance', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'Provenance fraud is a separate but related threat. Wolfgang Beltracchi, often called the most successful '
        'art forger since World War II, sold more than 300 fake paintings attributed to artists like Max Ernst, '
        'Fernand Leger, and Heinrich Campendonk. He and his wife Helen were convicted in 2011 on charges tied '
        'to 14 works sold for $45 million.',
        style_body
    ))

    story.append(Paragraph(
        'Their method was telling. Beltracchi did not just forge paintings. He forged the provenance. The couple '
        'bought old frames and canvases at flea markets, used a 1920s camera to stage photographs that made the '
        'works look like they had been in private collections for decades, and invented fictional collectors to '
        'fill the ownership chain. He painted works in the style of known artists but chose subjects that filled '
        'gaps in their catalogs, giving art historians the thrill of a "discovery."',
        style_body
    ))

    story.append(Paragraph(
        'He was caught because he ran out of his usual zinc white pigment and bought a substitute that contained '
        'titanium, a compound not used in white paint until the 1920s. Scientific analysis of <i>Red Picture with '
        'Horses</i>, which had sold at auction for 2.8 million euros as a Campendonk, found the titanium and '
        'unraveled the scheme.',
        style_body
    ))

    story.append(Spacer(1, 8))
    story.append(PullQuoteBox(
        "Documents can be faked as easily as paintings. A receipt, a photograph, a stamp on the back "
        "of a canvas. None of these should be taken at face value without independent verification."
    ))
    story.append(Spacer(1, 20))

    # ── Section: How Auction Houses Verify Provenance ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('How Auction Houses Verify Provenance', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'Christie\'s and Sotheby\'s both employ teams of specialists who research ownership chains before '
        'accepting a consignment. The standard process involves several steps.',
        style_body
    ))

    story.append(Paragraph(
        'The consignor provides whatever documentation they have: receipts, prior auction records, gallery '
        'invoices, family records. The auction house\'s research department then tries to fill gaps, checking '
        'published catalogs, exhibition records, and their own archives of past sales.',
        style_body
    ))

    story.append(Paragraph(
        'Every work above a certain value threshold gets checked against the Art Loss Register (ALR), a private '
        'database of over 700,000 stolen, missing, and looted items. The ALR runs more than 400,000 searches '
        'per year on behalf of more than 130 subscribing auction houses, dealers, and museums. If a work matches '
        'an entry, the sale stops until the claim is resolved.',
        style_body
    ))

    story.append(Paragraph(
        'For works that changed hands in Europe between 1933 and 1945, both major houses have dedicated provenance '
        'research teams. Sotheby\'s publishes its commitment to resolving displaced-art claims on its website and '
        'has a formal process for handling them. Christie\'s follows a similar protocol.',
        style_body
    ))

    story.append(Paragraph(
        'These checks are good but not perfect. Auction houses face a conflict of interest: they earn commissions '
        'on sales, so flagging a provenance problem costs them money directly. Buyers should treat auction house '
        'due diligence as a starting point, not a guarantee.',
        style_body
    ))

    story.append(PageBreak())

    # ── Section: Digital Provenance and Blockchain ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('Digital Provenance and Blockchain', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'Blockchain-based provenance tracking has moved from theory to limited practice. Platforms like Verisart '
        '(with over 250,000 artworks registered) and Artory create digital certificates of authenticity tied to '
        'blockchain ledgers, recording ownership transfers in a way that is hard to alter after the fact.',
        style_body
    ))

    story.append(Paragraph(
        'Both Sotheby\'s and Christie\'s now offer blockchain-verified provenance for some high-value lots. '
        'A 2023 Deloitte Art &amp; Finance Report found that 78% of collectors said they would pay more for art '
        'with blockchain-verified provenance.',
        style_body
    ))

    story.append(Paragraph(
        'In November 2024, Germann Auction became the first house to conduct a sale where authentication relied '
        'entirely on artificial intelligence, though experts in the field stress that AI should supplement, not '
        'replace, human judgment and scientific testing.',
        style_body
    ))

    story.append(Paragraph(
        'These tools solve a real problem, but they only work going forward. Blockchain cannot retroactively '
        'verify a painting\'s ownership during the 1940s. For older works, traditional paper-based provenance '
        'research remains the only option.',
        style_body
    ))
    story.append(Spacer(1, 20))

    # ── Section: What Investors Should Check Before Buying ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('What Investors Should Check Before Buying', style_h2))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        'Provenance due diligence is not optional for anyone treating art as a financial asset. Here is what '
        'to look for.',
        style_body
    ))

    bullets = [
        'Ask to see the full provenance before you commit to a purchase. If a seller says they will share it only after you buy, walk away. That is the single biggest red flag in art transactions.',
        'Check that the documentation specifically describes the work, including dimensions, medium, date, and title. A receipt that says "oil painting" without matching it to the exact piece is not real provenance.',
        'Look for original documents, not photocopies, unless the originals are held at a known institution you can verify independently.',
        'Cross-reference ownership claims against auction databases, catalogues raisonne, and exhibition records. A claimed exhibition history that does not appear in the museum\'s published catalog is a problem.',
        'Be wary of provenance that looks too clean. Most real ownership chains have some gaps or unclear periods. A perfectly smooth record from artist to present day can signal fabrication.',
        'Run the work through the Art Loss Register or ask your dealer to provide proof that they have done so.',
        'For any work created before 1945 with European provenance, investigate the ownership chain during the Nazi era specifically. Even a one-year gap between 1933 and 1945 can create liability.',
    ]

    for b in bullets:
        story.append(Paragraph(
            '<bullet>&bull;</bullet> ' + b,
            style_bullet
        ))

    story.append(Spacer(1, 10))

    story.append(Paragraph(
        'For fractional art investment platforms like Masterworks, provenance verification happens before a work '
        'enters the portfolio. The platform\'s acquisition team checks ownership history, exhibition records, and '
        'database clearances as part of the buying process. Individual investors on the platform do not need to run '
        'these checks themselves, but understanding what goes into them helps you evaluate the quality of any art '
        'investment offering.',
        style_body
    ))

    story.append(PageBreak())

    # ── Section: The Bottom Line ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('The Bottom Line', style_h2))
    story.append(Spacer(1, 4))

    bottom_bullets = [
        'Provenance, the documented ownership history of an artwork, can add up to 54% to auction prices and boost annualized returns by 5% to 16%, according to peer-reviewed research.',
        'Celebrity and collector provenance drives outsized results: the Paul Allen sale brought $1.5 billion, and single-owner auctions now account for nearly a third of major house volume.',
        'Gaps in provenance, especially during 1933 to 1945, create real legal and financial risk. New restitution mechanisms in Germany and France mean claims are getting easier to file, not harder.',
        'Forged provenance is as dangerous as forged paintings. The Beltracchi case proved that fabricated photographs, receipts, and collector histories can fool experts for years.',
        'Auction house due diligence and the Art Loss Register (700,000+ items, 400,000+ annual checks) provide baseline protection, but buyers should treat them as a floor, not a ceiling.',
        'Digital tools like blockchain provenance are gaining traction, but they only track ownership going forward. For older works, paper records and specialist research remain the standard.',
    ]

    for b in bottom_bullets:
        story.append(Paragraph(
            '<bullet>&bull;</bullet> ' + b,
            style_bottom_line_bullet
        ))

    story.append(Spacer(1, 20))
    story.append(ThinRule(CONTENT_W))
    story.append(Spacer(1, 10))

    # ── FAQ Section ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('Frequently Asked Questions', style_h2))
    story.append(Spacer(1, 8))

    faqs = [
        (
            'What is provenance in art investing?',
            'Provenance is the full ownership history of a work of art, documented through sales records, exhibition catalogs, published references, and other evidence. For investors, it functions as a title history similar to what you would review before buying real estate. Strong provenance increases both the price and the liquidity of a work, while weak or missing provenance can reduce value by double-digit percentages and expose the owner to legal claims.'
        ),
        (
            'How much does provenance affect an artwork\'s price?',
            'Academic research on nearly three million auction transactions found that exhibition history adds about 42% to hammer prices, published literature references add 54%, pedigree records add 21%, and certificates add 14%. Celebrity provenance can push results even higher: The Pattie Boyd Collection at Christie\'s in March 2024 sold for more than seven times its high estimate.'
        ),
        (
            'Can provenance be faked?',
            'Yes. The most famous modern case involved Wolfgang Beltracchi, who forged more than 300 paintings and fabricated their ownership records using staged photographs, fake receipts, and invented collectors. He was only caught when scientific analysis found a modern pigment in one of his works. Investors should verify provenance through independent sources, not just the documents a seller provides.'
        ),
        (
            'How do I check if an artwork has been stolen or looted?',
            'The Art Loss Register maintains a database of over 700,000 stolen, missing, and looted items and runs more than 400,000 checks per year. You can request a search through the ALR directly, or ask your dealer or auction house to provide proof of clearance. For works with European provenance before 1945, the Center for Art Law and various national registries maintain additional databases of Nazi-era claims.'
        ),
        (
            'Does blockchain solve the provenance problem?',
            'Blockchain creates a tamper-resistant record of ownership transfers, and platforms like Verisart and Artory have registered hundreds of thousands of works. Both major auction houses now use blockchain for some high-value lots. But blockchain only records information entered into it. It cannot verify that a painting\'s ownership in the 1940s was legitimate, or that the person registering a work today is its rightful owner. It is a useful layer of protection for new transactions, not a fix for historical gaps.'
        ),
    ]

    for q, a in faqs:
        story.append(Paragraph(q, style_faq_q))
        story.append(Paragraph(a, style_faq_a))

    story.append(PageBreak())

    # ── Sources ──
    story.append(AccentRule(60))
    story.append(Spacer(1, 6))
    story.append(Paragraph('Sources', style_h2))
    story.append(Spacer(1, 8))

    sources = [
        'Pownall, R., Graddy, K. "In Art We Trust." Management Science, 2022.',
        'Li, Y., et al. "Pricing Art and the Art of Pricing: On Returns and Risk in Art Auction Markets." European Financial Management, 2022.',
        'Center for Art Law. "Nazi-Era Looted Art Restitution Cases Project 2024-2025 Annual Report." December 2025.',
        'Harvard Law School, HALO. "Eighty Years Later, Progress of Nazi-Era Restitution Remains Inconsistent." March 2025.',
        'Art Loss Register. "About Us." 2025.',
        'MyArtBroker. "Art in the Limelight: The Power of Celebrity Provenance at Auction." 2024.',
        'Artnet News. "Paul Allen\'s Masterpiece-Filled Collection Sells for $1.5 Billion at Christie\'s." November 2022.',
        'Artefact Fine Art. "The Impact of Provenance on Value." 2024.',
        'Center for Art Law. "Tricking the Art Market: On Forgery, Beltracchi, and Scientific Technology." 2025.',
        'Sotheby\'s. "Provenance Research." 2025.',
        'Citywealth. "Provenance, Policy Wording and Fraud: Inside the 2025 Insurance Landscape." 2025.',
        'Deloitte. "Art &amp; Finance Report." 2023.',
    ]

    for i, src in enumerate(sources, 1):
        story.append(Paragraph(f'{i}. {src}', style_source))

    story.append(PageBreak())

    # ── Disclosure Page ──
    story.append(Spacer(1, 80))
    story.append(AccentRule(60))
    story.append(Spacer(1, 16))
    story.append(Paragraph('Important Disclosures', style_h2))
    story.append(Spacer(1, 20))

    story.append(Paragraph(
        'This document is for informational purposes only and is not an offer to sell or a solicitation of '
        'an offer to buy any securities.',
        style_disclosure
    ))
    story.append(Spacer(1, 8))
    story.append(Paragraph(
        'See important Reg A disclosures at masterworks.com/cd',
        style_disclosure
    ))
    story.append(Spacer(1, 8))
    story.append(Paragraph(
        'Investing involves risk, including the possible loss of principal. Past performance is not indicative '
        'of future returns. The information contained herein is based on data obtained from sources believed to '
        'be reliable but is not guaranteed as to accuracy or completeness.',
        style_disclosure
    ))
    story.append(Spacer(1, 8))
    story.append(Paragraph(
        'Masterworks is not a registered investment adviser and does not provide investment advice. This material '
        'is provided for educational purposes only.',
        style_disclosure
    ))
    story.append(Spacer(1, 40))
    story.append(ThinRule(CONTENT_W))
    story.append(Spacer(1, 16))

    story.append(Paragraph(
        'Masterworks Academy',
        ParagraphStyle('BrandFooter', fontName='Helvetica-Bold', fontSize=11, textColor=PRIMARY_TEXT)
    ))
    story.append(Paragraph(
        'masterworks.com/academy',
        ParagraphStyle('BrandURL', fontName='Helvetica', fontSize=9, textColor=MUTED)
    ))

    # ── Build ──
    doc.build(story, onFirstPage=footer_template, onLaterPages=footer_template)
    print(f'PDF generated: {output_path}')


if __name__ == '__main__':
    build_pdf()
