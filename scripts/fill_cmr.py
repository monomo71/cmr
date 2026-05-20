#!/usr/bin/env python3
import io
import json
import sys
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics

MM_TO_PT = 72.0 / 25.4
GLOBAL_Y_SHIFT_PT = 3.0 * MM_TO_PT


def write_block(c, text, x, y_top, w, font='Helvetica', size=8.0, leading=None, max_lines=None):
    text = (text or '').strip()
    if not text:
        return

    if leading is None:
        leading = size * 1.15

    words_lines = []
    for raw_line in text.splitlines() or ['']:
        raw_line = raw_line.strip()
        if raw_line == '':
            words_lines.append('')
            continue

        words = raw_line.split(' ')
        current = ''
        for word in words:
            candidate = word if current == '' else f'{current} {word}'
            if pdfmetrics.stringWidth(candidate, font, size) <= w:
                current = candidate
            else:
                if current:
                    words_lines.append(current)
                current = word
        if current:
            words_lines.append(current)

    if max_lines is not None:
        words_lines = words_lines[:max_lines]

    y = PAGE_H - (y_top + GLOBAL_Y_SHIFT_PT) - size
    c.setFont(font, size)
    for line in words_lines:
        c.drawString(x, y, line)
        y -= leading


def draw_overlay(c, data):
    write_block(c, data.get('field1', ''), 44, 36, 250, size=8.5)
    write_block(c, data.get('field2', ''), 44, 106, 250, size=8.5)
    write_block(c, data.get('field3', ''), 44, 178, 250, size=8)
    write_block(c, data.get('field4', ''), 44, 226, 250, size=8)
    write_block(c, data.get('field5', ''), 44, 273, 250, size=8)

    write_block(c, data.get('field16', ''), 304, 36, 248, size=8.5)
    write_block(c, data.get('field17', ''), 304, 106, 248, size=8.5)
    write_block(c, data.get('field18', ''), 304, 178, 248, size=8)

    start_y = 322
    row_height = 20
    items = data.get('items', [])
    for idx, item in enumerate(items[:10]):
        y = start_y + (idx * row_height)
        write_block(c, item.get('field6', ''), 44, y, 166, size=7, max_lines=2)
        # Fijn-afstelling op basis van gemarkeerde kolomstarts:
        # 7/8 iets naar links, 9 naar rechts, 10/11/12 één kolom naar rechts.
        write_block(c, item.get('field7', ''), 150, y, 58, size=7, max_lines=2)
        write_block(c, item.get('field8', ''), 205, y, 60, size=7, max_lines=2)
        write_block(c, item.get('field9', ''), 266, y, 96, size=7, max_lines=2)
        write_block(c, item.get('field10', ''), 366, y, 60, size=7, max_lines=2)
        write_block(c, item.get('field11', ''), 429, y, 62, size=7, max_lines=2)
        write_block(c, item.get('field12', ''), 495, y, 58, size=7, max_lines=2)

    write_block(c, data.get('field13', ''), 44, 538, 250, size=8)
    write_block(c, data.get('field14', ''), 44, 616, 250, size=8)
    write_block(c, data.get('field19', ''), 304, 538, 248, size=8)
    write_block(c, data.get('field20', ''), 304, 615, 248, size=7.5)

    write_block(c, data.get('field21', ''), 44, 697, 250, size=8)
    write_block(c, data.get('field15', ''), 304, 697, 248, size=8)

    write_block(c, data.get('field22', ''), 44, 728, 162, size=8)
    write_block(c, data.get('field23', ''), 216, 728, 162, size=8)
    write_block(c, data.get('field24', ''), 389, 728, 162, size=8)


def main():
    if len(sys.argv) != 4:
        print('Usage: fill_cmr.py <template_pdf> <json_data_file> <output_pdf>', file=sys.stderr)
        sys.exit(2)

    template_path = Path(sys.argv[1])
    data_path = Path(sys.argv[2])
    output_path = Path(sys.argv[3])

    data = json.loads(data_path.read_text(encoding='utf-8'))

    reader = PdfReader(str(template_path))
    writer = PdfWriter()

    global PAGE_H

    for page in reader.pages:
        page_w = float(page.mediabox.width)
        page_h = float(page.mediabox.height)
        PAGE_H = page_h

        packet = io.BytesIO()
        c = canvas.Canvas(packet, pagesize=(page_w, page_h))
        draw_overlay(c, data)
        c.save()

        packet.seek(0)
        overlay = PdfReader(packet).pages[0]
        page.merge_page(overlay)
        writer.add_page(page)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open('wb') as f:
        writer.write(f)


if __name__ == '__main__':
    main()
