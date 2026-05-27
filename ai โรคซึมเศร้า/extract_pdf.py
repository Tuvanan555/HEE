import fitz  # PyMuPDF
import sys
import json

pdf_path = r"D:\ข้อเสนอโครงการ.pdf"

try:
    doc = fitz.open(pdf_path)
    total = len(doc)
    
    # Write to file with explicit UTF-8
    with open(r"d:\ai โรคซึมเศร้า\pdf_out.txt", 'w', encoding='utf-8') as f:
        f.write(f"Total pages: {total}\n")
        f.write("=" * 80 + "\n")
        for i, page in enumerate(doc):
            text = page.get_text("text")
            if text.strip():
                f.write(f"\n--- PAGE {i+1} ---\n\n")
                f.write(text)
    doc.close()
    print(f"Done. Wrote {total} pages to pdf_out.txt")
except Exception as e:
    print(f"Error: {e}", file=sys.stderr)
