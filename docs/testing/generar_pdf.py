"""Genera el anexo PDF de testing a partir de los Markdown de esta carpeta.

Se usa solo para documentación; no es una dependencia de la aplicación web.
Requiere reportlab en el equipo que regenera el anexo.
"""

from __future__ import annotations

import html
import re
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate,
    Frame,
    KeepTogether,
    PageBreak,
    PageTemplate,
    Paragraph,
    Spacer,
    Table,
    TableStyle,
)


ROOT = Path(__file__).resolve().parents[2]
SOURCE_DIR = Path(__file__).resolve().parent
OUTPUT = ROOT / "output" / "pdf" / "Informe_testing_NovaCycle.pdf"
SOURCES = [
    "plan_de_pruebas.md",
    "casos_y_trazabilidad.md",
    "defectos.md",
    "informe_ejecucion.md",
]

INK = colors.HexColor("#17352c")
GREEN = colors.HexColor("#147b52")
PALE = colors.HexColor("#eaf4ee")
LIGHT = colors.HexColor("#f3f7f4")
GRAY = colors.HexColor("#52605a")


def register_fonts() -> tuple[str, str]:
    candidates = [
        (Path("C:/Windows/Fonts/arial.ttf"), Path("C:/Windows/Fonts/arialbd.ttf")),
        (
            Path("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"),
            Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"),
        ),
    ]
    for normal, bold in candidates:
        if normal.exists() and bold.exists():
            pdfmetrics.registerFont(TTFont("NovaBody", str(normal)))
            pdfmetrics.registerFont(TTFont("NovaBold", str(bold)))
            pdfmetrics.registerFontFamily("NovaBody", normal="NovaBody", bold="NovaBold")
            return "NovaBody", "NovaBold"
    return "Helvetica", "Helvetica-Bold"


FONT, FONT_BOLD = register_fonts()
styles = getSampleStyleSheet()
styles.add(
    ParagraphStyle(
        name="NovaTitle",
        fontName=FONT_BOLD,
        fontSize=25,
        leading=30,
        textColor=INK,
        alignment=TA_LEFT,
        spaceAfter=14,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaSubtitle",
        fontName=FONT,
        fontSize=13,
        leading=19,
        textColor=GRAY,
        spaceAfter=16,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaH1",
        fontName=FONT_BOLD,
        fontSize=16,
        leading=21,
        textColor=INK,
        spaceBefore=7,
        spaceAfter=12,
        keepWithNext=True,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaH2",
        fontName=FONT_BOLD,
        fontSize=12,
        leading=16,
        textColor=GREEN,
        spaceBefore=14,
        spaceAfter=7,
        keepWithNext=True,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaH3",
        fontName=FONT_BOLD,
        fontSize=10,
        leading=14,
        textColor=INK,
        spaceBefore=11,
        spaceAfter=5,
        keepWithNext=True,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaBody",
        fontName=FONT,
        fontSize=8.8,
        leading=13,
        textColor=INK,
        spaceAfter=7,
        splitLongWords=1,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaBullet",
        parent=styles["NovaBody"],
        leftIndent=15,
        firstLineIndent=-10,
        spaceAfter=3,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaTable",
        fontName=FONT,
        fontSize=7.4,
        leading=10.5,
        textColor=INK,
        splitLongWords=1,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaTableHead",
        parent=styles["NovaTable"],
        fontName=FONT_BOLD,
        textColor=colors.white,
    )
)
styles.add(
    ParagraphStyle(
        name="NovaCode",
        fontName="Courier",
        fontSize=7.3,
        leading=10.5,
        textColor=INK,
        leftIndent=7,
        spaceAfter=0,
        splitLongWords=1,
    )
)


def inline(value: str) -> str:
    value = html.escape(value.strip())
    value = re.sub(r"\*\*(.+?)\*\*", r"<b>\1</b>", value)
    value = re.sub(r"`([^`]+)`", r"<font color='#116844'>\1</font>", value)
    return value


def parse_table(lines: list[str], width: float) -> Table:
    rows: list[list[str]] = []
    for line in lines:
        cells = [cell.strip() for cell in line.strip().strip("|").split("|")]
        if all(re.fullmatch(r":?-{3,}:?", cell or "") for cell in cells):
            continue
        rows.append(cells)
    count = max(len(row) for row in rows)
    for row in rows:
        row.extend([""] * (count - len(row)))

    if count == 3:
        weights = [1.05, 1.1, 1.85]
    elif count == 4:
        weights = [0.8, 1.25, 1.05, 1.7]
    else:
        weights = [1] * count
    total = sum(weights)
    widths = [width * weight / total for weight in weights]
    content = [
        [Paragraph(inline(cell), styles["NovaTableHead" if index == 0 else "NovaTable"]) for cell in row]
        for index, row in enumerate(rows)
    ]
    table = Table(content, colWidths=widths, repeatRows=1, hAlign="LEFT")
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), GREEN),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, LIGHT]),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 7),
                ("RIGHTPADDING", (0, 0), (-1, -1), 7),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
                ("LINEBELOW", (0, 0), (-1, 0), 0.5, GREEN),
                ("LINEBELOW", (0, -1), (-1, -1), 0.4, colors.HexColor("#d9e6de")),
            ]
        )
    )
    return table


def markdown_to_story(markdown: str, width: float) -> list:
    story: list = []
    lines = markdown.splitlines()
    paragraph: list[str] = []
    table_lines: list[str] = []
    code_lines: list[str] = []
    in_code = False
    pending_h3 = None

    def flush_paragraph() -> None:
        nonlocal pending_h3
        if paragraph:
            joined = " ".join(part.strip() for part in paragraph)
            body = Paragraph(inline(joined), styles["NovaBody"])
            if pending_h3 is not None:
                story.append(KeepTogether([pending_h3, body]))
                pending_h3 = None
            else:
                story.append(KeepTogether([body]))
            paragraph.clear()

    def flush_table() -> None:
        if table_lines:
            story.append(parse_table(table_lines, width))
            story.append(Spacer(1, 9))
            table_lines.clear()

    def flush_code() -> None:
        if code_lines:
            content = []
            for line in code_lines:
                indentation = len(line) - len(line.lstrip(" "))
                rendered = "&nbsp;" * indentation + html.escape(line[indentation:])
                content.append([Paragraph(rendered or "&nbsp;", styles["NovaCode"])])
            block = Table(content, colWidths=[width], hAlign="LEFT")
            block.setStyle(
                TableStyle(
                    [
                        ("BACKGROUND", (0, 0), (-1, -1), LIGHT),
                        ("TOPPADDING", (0, 0), (-1, -1), 1),
                        ("BOTTOMPADDING", (0, 0), (-1, -1), 1),
                    ]
                )
            )
            story.extend([block, Spacer(1, 9)])
            code_lines.clear()

    for line in lines:
        if line.startswith("```"):
            flush_paragraph()
            flush_table()
            if in_code:
                flush_code()
            in_code = not in_code
            continue
        if in_code:
            code_lines.append(line)
            continue
        if line.startswith("|"):
            flush_paragraph()
            table_lines.append(line)
            continue
        flush_table()
        if not line.strip():
            flush_paragraph()
            continue
        if line.startswith("### "):
            flush_paragraph()
            if pending_h3 is not None:
                story.append(pending_h3)
            pending_h3 = Paragraph(inline(line[4:]), styles["NovaH3"])
        elif line.startswith("## "):
            flush_paragraph()
            if pending_h3 is not None:
                story.append(pending_h3)
                pending_h3 = None
            if line.startswith("## 5. Reproducción en PowerShell") or line.startswith("## 3. Defectos encontrados y regresión"):
                story.append(PageBreak())
            story.append(Paragraph(inline(line[3:]), styles["NovaH2"]))
        elif line.startswith("# "):
            flush_paragraph()
            if pending_h3 is not None:
                story.append(pending_h3)
                pending_h3 = None
            story.append(Paragraph(inline(line[2:]), styles["NovaH1"]))
        elif re.match(r"^(?:- |\d+\. )", line):
            flush_paragraph()
            if pending_h3 is not None:
                story.append(pending_h3)
                pending_h3 = None
            item = re.sub(r"^(?:- |\d+\. )", "", line)
            story.append(Paragraph("&#8226;&nbsp;&nbsp;" + inline(item), styles["NovaBullet"]))
        else:
            paragraph.append(line)
    flush_paragraph()
    flush_table()
    flush_code()
    if pending_h3 is not None:
        story.append(pending_h3)
    return story


def draw_page(canvas, doc) -> None:
    canvas.saveState()
    page_width, page_height = A4
    canvas.setStrokeColor(colors.HexColor("#d5e3da"))
    canvas.setLineWidth(0.7)
    canvas.line(46, page_height - 39, page_width - 46, page_height - 39)
    canvas.setFillColor(GREEN)
    canvas.setFont(FONT_BOLD, 8)
    canvas.drawString(46, page_height - 30, "NOVACYCLE  /  TESTING Y VALIDACIÓN")
    canvas.setFillColor(GRAY)
    canvas.setFont(FONT, 8)
    canvas.drawString(46, 28, "06/11/2026  -  Versión 1.0")
    canvas.drawRightString(page_width - 46, 28, f"Página {doc.page}")
    canvas.restoreState()


def main() -> None:
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    page_width, page_height = A4
    margin = 46
    width = page_width - margin * 2
    doc = BaseDocTemplate(
        str(OUTPUT),
        pagesize=A4,
        leftMargin=margin,
        rightMargin=margin,
        topMargin=58,
        bottomMargin=48,
        title="NovaCycle - Plan, casos y resultados de testing",
        author="Equipo NovaCycle",
    )
    frame = Frame(margin, 48, width, page_height - 106, leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
    doc.addPageTemplates(PageTemplate(id="normal", frames=frame, onPage=draw_page))

    story = [
        Spacer(1, 115),
        Paragraph("Testing y validación", styles["NovaTitle"]),
        Paragraph("Plan, casos, trazabilidad, defectos y resultados de NovaCycle", styles["NovaSubtitle"]),
        Spacer(1, 20),
    ]
    cover = Table(
        [
            [Paragraph("15 scripts aprobados", styles["NovaH3"]), Paragraph("2 defectos corregidos", styles["NovaH3"])],
            [Paragraph("3 unitarios y 12 de integración", styles["NovaBody"]), Paragraph("Validación con usuarios pendiente", styles["NovaBody"])],
        ],
        colWidths=[width / 2, width / 2],
    )
    cover.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), PALE),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 12),
                ("RIGHTPADDING", (0, 0), (-1, -1), 12),
                ("TOPPADDING", (0, 0), (-1, -1), 9),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 9),
            ]
        )
    )
    story.append(cover)
    story.append(Spacer(1, 25))
    story.append(
        Paragraph(
            "La verificación automática está aprobada en un Docker de pruebas aislado. "
            "La aceptación visual y por perfiles no se declara aprobada hasta que se ejecute y documente.",
            styles["NovaBody"],
        )
    )
    story.append(PageBreak())

    for index, name in enumerate(SOURCES):
        if index in (1, 3):
            story.append(PageBreak())
        story.extend(markdown_to_story((SOURCE_DIR / name).read_text(encoding="utf-8"), width))

    doc.build(story)
    print(OUTPUT)


if __name__ == "__main__":
    main()
