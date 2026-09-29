<?php

namespace App\Repositories;

use App\Models\Verification;
use Illuminate\Support\Facades\Log;
use TCPDF;

class BVN_PDF_Repository
{


    public function plasticPDF($bvn_no)
    {
        // Check if record exists and retrieve the latest record
        $verifiedRecord = Verification::where(function ($q) use ($bvn_no) {
            $q->where('idno', $bvn_no)
              ->orWhere('number_nin', $bvn_no)
              ->orWhere('id', $bvn_no);
        })
        ->latest()
        ->first();

        if ($verifiedRecord) {
            $bvnData = [
                "bvn" => $verifiedRecord->idno ?? ($verifiedRecord->number_nin ?? ''),
                "fName" => $verifiedRecord->firstname ?? ($verifiedRecord->first_name ?? ''),
                "sName" => $verifiedRecord->surname ?? ($verifiedRecord->last_name ?? ''),
                "mName" => $verifiedRecord->middlename ?? '',
                "tId" => $verifiedRecord->trackingId ?? '',
                "address" => $verifiedRecord->residence_address ?? ($verifiedRecord->address ?? ''),
                "lga" => $verifiedRecord->residence_lga ?? ($verifiedRecord->lga ?? ''),
                "state" => $verifiedRecord->residence_state ?? ($verifiedRecord->state ?? ''),
                "gender" => ($verifiedRecord->gender === 'Male' || $verifiedRecord->gender === 'M') ? "M" : "F",
                "dob" => $verifiedRecord->birthdate ?? '',
                "photo" => preg_replace('/^data:image\/\w+;base64,/', '', $verifiedRecord->photo_path ?? ($verifiedRecord->photo ?? ''))
            ];

            $names = trim(html_entity_decode($bvnData['fName']) . ' ' . html_entity_decode($bvnData['sName']));

            // Initialize TCPDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->setPrintHeader(false);
            $pdf->SetCreator('Abu');
            $pdf->SetAuthor('Zulaiha');
            $pdf->SetTitle($names);
            $pdf->SetSubject('Plastic');
            $pdf->SetKeywords('plastic, TCPDF, PHP');
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();
            $pdf->SetFont('dejavuserifcondensedbi', '', 12);

            // Use JPG images
            $pdf->Image(public_path('assets/card_and_Slip/bvn.jpg'), 69.5, 48, 78, 50, 'JPG', '', '', false, 300, '', false, false, 0);
            $pdf->Image(public_path('assets/card_and_Slip/finger.jpg'), 69.3, 101, 78, 50, 'JPG', '', '', false, 300, '', false, false, 1);

            // Add image from base64
            $photo = $bvnData['photo'];
            if (!empty($photo)) {
                $imgdata = base64_decode($photo);
                if ($imgdata !== false) {
                    $pdf->Image('@' . $imgdata, 73.5, 65.7, 17.8, 22, 'JPG', '', '', false, 300, '', false, false, 0);
                }
            }

            // Add text
            $sur = html_entity_decode($bvnData['sName']);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Text(93.3, 66.5, strtoupper($sur));

            $othername = trim(html_entity_decode($bvnData['fName']) . ', ' . html_entity_decode($bvnData['mName']));
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Text(93.3, 73.5, strtoupper($othername));

            $dob = $bvnData['dob'];
            $newD = strtotime($dob);
            $cdate = $newD ? date("d M Y", $newD) : $dob;
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(93.3, 81.2, $cdate);

            $gender = $bvnData['gender'];
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Text(114, 81, $gender);

            $issueD = date("d M Y");
            $pdf->SetFont('helvetica', '', 6);
            $pdf->Text(129.5, 79, $issueD);

            // Format BVN
            $bvn = $bvnData['bvn'];
            $pdf->setTextColor(0, 0, 0);
            $newBVN = strlen($bvn) === 11 ? (substr($bvn, 0, 4) . " " . substr($bvn, 4, 3) . " " . substr($bvn, 7)) : $bvn;
            $pdf->SetFont('helvetica', '', 15);
            $pdf->Text(91, 90, $newBVN);

            // Save and download PDF
            $filename =  'Plastic BVN ID - ' . $bvn_no . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S');

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } else {
            return response()->json([
                "message" => "Error",
                "errors" => ["Not Found" => "Verification record not found!"]
            ], 422);
        }
    }
}
