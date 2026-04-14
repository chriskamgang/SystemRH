<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    /**
     * Stocker un nouveau document
     */
    public function store(Request $request, User $user)
    {
        // 1. Valider la limite de 10 documents
        if ($user->documents()->count() >= 10) {
            return redirect()->back()->with('error', 'Cet employé a déjà atteint la limite de 10 documents.');
        }

        // 2. Valider le fichier
        $request->validate([
            'title' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('document');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('documents/' . $user->id, $fileName, 'public');

            EmployeeDocument::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return redirect()->back()->with('success', 'Document ajouté avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
        }
    }

    /**
     * Télécharger un document
     */
    public function download(EmployeeDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return redirect()->back()->with('error', 'Le fichier n\'existe plus sur le serveur.');
        }

        return Storage::disk('public')->download($document->file_path, $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION));
    }

    /**
     * Supprimer un document
     */
    public function destroy(EmployeeDocument $document)
    {
        try {
            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Supprimer l'enregistrement en DB
            $document->delete();

            return redirect()->back()->with('success', 'Document supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}
