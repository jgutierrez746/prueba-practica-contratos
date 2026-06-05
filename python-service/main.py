from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.responses import StreamingResponse
import fitz  # PyMuPDF
import io
import os
import tempfile

app = FastAPI(title="Microservicio de Estampado de Marca de Agua")

@app.post("/watermark")
async def apply_watermark(
    pdf_file: UploadFile = File(...),
    watermark_image: UploadFile = File(...)
):
    temp_pdf_path = None
    temp_img_path = None
    
    try:
        pdf_contents = await pdf_file.read()
        image_contents = await watermark_image.read()

        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as temp_pdf:
            temp_pdf.write(pdf_contents)
            temp_pdf_path = temp_pdf.name

        with tempfile.NamedTemporaryFile(delete=False, suffix=".png") as temp_img:
            temp_img.write(image_contents)
            temp_img_path = temp_img.name

        pdf_document = fitz.open(temp_pdf_path)

        for page_number in range(len(pdf_document)):
            page = pdf_document[page_number]
            page_rect = page.rect
            
            page.insert_image(
                page_rect, 
                filename=temp_img_path, 
                overlay=True, 
                keep_proportion=True
            )

        output_buffer = io.BytesIO()
        pdf_document.save(output_buffer, garbage=3, deflate=True)
        output_buffer.seek(0)

        pdf_document.close()

        return StreamingResponse(
            output_buffer, 
            media_type="application/pdf",
            headers={"Content-Disposition": "attachment; filename=watermarked.pdf"}
        )

    except Exception as e:
        print(f"Error interno en el microservicio de Python: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error al procesar los archivos: {str(e)}")
        
    finally:
        if temp_pdf_path and os.path.exists(temp_pdf_path):
            os.remove(temp_pdf_path)
        if temp_img_path and os.path.exists(temp_img_path):
            os.remove(temp_img_path)