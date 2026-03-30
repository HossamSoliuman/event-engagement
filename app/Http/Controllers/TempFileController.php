<?php

namespace App\Http\Controllers;

use App\Models\TempFile;
use Illuminate\Http\Request;

class TempFileController extends Controller
{
    public function store(Request $request)
    {
            $file = $request->file('file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $date = time();
            $fileName = str_replace(' ', '_', $originalName) . '_' . $date . '.' . $file->extension();
            $file->storeAs('temp', $fileName);
            TempFile::create([
                'file_name' => $fileName
            ]);
            return $fileName;

    }

}
