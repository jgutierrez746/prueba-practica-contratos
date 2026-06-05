<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        // Trae los documentos vinculados al usuario, ordenados del más nuevo al más viejo, paginados de a 10
        $documents = $request->user()->documents()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($documents, 200);
    }

    /**
     * Registrar un nuevo contrato enviando los archivos al servicio de Python.
     */
    public function store(Request $request)
    {
        // visualizar tipo de archivo cargado y mime real del pdf
        // return response()->json([
        //     'mime_type' => $request->file('pdf_file')?->getMimeType(),
        //     'client_mime' => $request->file('pdf_file')?->getClientMimeType()
        // ]);

        $validator = Validator::make($request->all(), [
            'contract_name' => 'required|string|max:255',
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // Máx 10MB
            'watermark_image' => 'required|file|mimes:jpg,jpeg,png|max:5120', // Máx 5MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $pdfFile = $request->file('pdf_file');
        $imageFile = $request->file('watermark_image');

        $bytes = $pdfFile->getSize();
        $fileSizeFormatted = $bytes >= 1048576 
            ? number_format($bytes / 1048576, 2) . ' MB' 
            : number_format($bytes / 1024, 2) . ' KB';

        // Recuperar la URL del contenedor de Python desde las variables del .env
        $pythonServiceUrl = env('PYTHON_SERVICE_URL');

        try {
            Log::info("Enviando el contrato '{$request->contract_name}' para estampar marca de agua.");

            // Petición HTTP Multipart enviando ambos binarios directos en memoria a Python
            $response = Http::asMultipart()
                ->attach(
                    'pdf_file', 
                    file_get_contents($pdfFile->getRealPath()), 
                    $pdfFile->getClientOriginalName()
                )
                ->attach(
                    'watermark_image', 
                    file_get_contents($imageFile->getRealPath()), 
                    $imageFile->getClientOriginalName()
                )
                ->post($pythonServiceUrl);

            if ($response->successful()) {
                Log::info("El servicio de Python procesó el archivo de manera exitosa.");

                // Generar una ruta y nombre interno seguro y único para el archivo físico
                $safeFilename = 'contracts/' . uniqid() . '_' . time() . '.pdf';
                
                // Guardar el binario del PDF modificado en el storage privado (storage/app/contracts/)
                Storage::disk('local')->put($safeFilename, $response->body());

                // Guardar la persistencia de la metadata en PostgreSQL
                $document = Document::create([
                    'user_id' => $user->id,
                    'contract_name' => $request->contract_name,
                    'original_filename' => $pdfFile->getClientOriginalName(),
                    'stored_path' => $safeFilename,
                    'file_size' => $fileSizeFormatted,
                    'status' => 'Procesado',
                ]);

                return response()->json([
                    'message' => 'Documento procesado con éxito.',
                    'document' => $document
                ], 201);
            }

            Log::error("El servicio de Python retornó una falla controlada: " . $response->body());
            return response()->json([
                'message' => 'El procesador externo falló al aplicar la marca de agua al documento.'
            ], 422);

        } catch (Exception $e) {
            // Si el contenedor de Python está apagado, caído o hay problemas de red interna en Docker
            Log::critical("Imposible establecer comunicación con el microservicio de Python. Detalles: " . $e->getMessage());
            return response()->json([
                'message' => 'El servicio de marca de agua no está disponible en este momento. Inténtelo más tarde.'
            ], 503);
        }
    }

    /**
     * Descargar  PDF físico con marca de agua.
     */
    public function download(Request $request, $id)
    {
        $document = $request->user()->documents()->find($id);

        if (!$document) {
            return response()->json(['message' => 'Documento no encontrado o no autorizado.'], 404);
        }

        $absolutePath = storage_path('app/private/' . $document->stored_path);

        if (!file_exists($absolutePath)) {
            return response()->json([
                'message' => 'El archivo físico no se encuentra en el almacenamiento.'
            ], 404);
        }

        return response()->download($absolutePath, $document->original_filename, [
            'Content-Type' => 'application/pdf',
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ]);
    }

    /**
     * Eliminar PDF y su registro en la base de datos.
     */
    public function destroy(Request $request, $id)
    {
        $document = $request->user()->documents()->find($id);

        if (!$document) {
            return response()->json(['message' => 'Documento no encontrado o no autorizado.'], 404);
        }

        $absolutePath = storage_path('app/private/' . $document->stored_path);

        try {
            if (file_exists($absolutePath)) {
                unlink($absolutePath); // Borrado físico directo en Linux
            }
            
            // Eliminar el registro en la base de datos
            $document->delete();

            return response()->json([
                'message' => 'Documento y archivo físico eliminados con éxito.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al intentar eliminar el documento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}