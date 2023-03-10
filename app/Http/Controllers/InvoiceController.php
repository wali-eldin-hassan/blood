<?php

namespace App\Http\Controllers;

use App\Models\BloodTest;
use App\Models\BloodWithdraw;
use App\Models\DoctorTest;
use App\Models\Donation;
use App\Models\Investigation;
use App\Models\Kid;
use App\Models\Order;
use App\Models\Polycythemia;
use App\Models\ViralTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class InvoiceController extends Controller
{
    public function banckInvoice()
    {
        $pdf = PDF::loadView('invoices.bank-invoice');
        return $pdf->stream('document.pdf');
    }

    public function donatiosCheck()
    {
        // $pdf = PDF::loadView('invoices.donatios-check');
        return view('invoices.donatios-check');

        // return $pdf->stream('document.pdf');
    }

    public function viralDiseases(Request $request)
    {

        $viralDiseases = ViralTest::where('result', '!=', null)->whereBetween('created_at', [$request->form_date, $request->to_date])->pluck('result');
        $HCV = 0;
        $HBV = 0;
        $HIV = 0;
        $SYPHILIS = 0;
        $NHCV = 0;
        $NHBV = 0;
        $NHIV = 0;
        $NSYPHILIS = 0;
        $list = [];

        $index = 0;
        foreach ($viralDiseases as $value) {
            $index++;
            $vals = json_decode($value);
            foreach ($vals as $val) {
                if ($val == 'HIV') {
                    $HIV++;
                } elseif ($val == 'Hcv') {
                    $HCV++;
                } elseif ($val == 'HBV') {
                    $HBV++;
                } elseif ($val == 'SYPHILIS') {
                    $SYPHILIS++;
                }
            }
        }
        // dd($x);

        //  foreach($viralDiseases as $key=>$value) {
        //  }

        return view('invoices.donersCheck', compact('HCV', 'HBV', 'HIV', 'SYPHILIS', 'index'));
    }

    public function printOrder($id)
    {
        $order = Order::where('id', $id)->with(['donations', 'bloods', 'person'])->first();
        $quantity = $order->bloods->pluck('quantity');
        $donation = Donation::where('order_id', $id)->first();
        $barcode = (string)$order->id;
        return view('invoices.order-invoice', compact('order', 'barcode', 'quantity'));
        // $pdf = PDF::loadView('invoices.order-invoice', compact('order', 'quantity','donation'));
        // return $pdf->stream('document.pdf');
    }

    public function printPolcythemias($id)
    {
        $polcythemia = Polycythemia::where('id', $id)->with(['bloodTest', 'doctorTest', 'bloodWithdraw', 'person'])->first();

        // $pdf = PDF::loadView('invoices.Polcythemias-invoice', compact('polcythemia'));
        // return $pdf->stream('document.pdf');
        $barcode = (string)$polcythemia->id;

        return view('invoices.Polcythemias-invoice', compact('polcythemia', 'barcode'));
    }

    public function donersWithDraw(Request $request)
    {
        $viralDiseases = ViralTest::where('result', '!=', null)->whereBetween('created_at', [$request->form_date, $request->to_date])->pluck('result');

        $HCV = 0;
        $HBV = 0;
        $HIV = 0;
        $SYPHILIS = 0;

        foreach ($viralDiseases as $value) {
            $vals = json_decode($value);
            foreach ($vals as $val) {
                if ($val == 'HIV') {
                    $HIV++;
                } elseif ($val == 'Hcv') {
                    $HCV++;
                } elseif ($val == 'HBV') {
                    $HBV++;
                } elseif ($val == 'SYPHILIS') {
                    $SYPHILIS++;
                }
            }
        }
        $donerCount = Donation::count();
        $unCompleteWithDraw = BloodWithdraw::where('faild', 1)->count();
        $Decent = BloodWithdraw::where('faild', 0)->count();
        $lowHemoglobin = BloodTest::where('HB', '<', 13)->count();
        $polcythemiaLowHemoglobin = Polycythemia::where('HB', '<', 13)->count();
        $lowHemoglobin = $lowHemoglobin + $polcythemiaLowHemoglobin;
        $ExclusionFromTheDoctor = DoctorTest::where('others', '!=', null)->count();
        return view('invoices.doners-with-draw-invoice', compact('donerCount', 'Decent', 'unCompleteWithDraw', 'lowHemoglobin', 'ExclusionFromTheDoctor', 'lowHemoglobin', 'HCV', 'HBV', 'HIV', 'SYPHILIS'));
    }

    public function ExclusionFromTheDoctor(Request $request)
    {
        $permanentTreatments = 0;
        $chronicDiseases = 0;
        $HHB = 0;
        $LHB = 0;
        $highBlood = 0;
        $lowBlood = 0;
        $others = 0;
        $usesAntibiotics = 0;
        $lowWeight = 0;
        $lessThan18 = 0;
        $ToothExtraction = 0;
        $doctorTests = DoctorTest::where('others', '!=', null)->whereBetween('created_at', [$request->form_date, $request->to_date])->pluck('others');
        $count = DoctorTest::where('others', '=', null)->count();
        foreach ($doctorTests as $doctorTest) {
            $vals = json_decode($doctorTest);
            foreach ($vals as $val) {
                if ($val == '???????????? ??????????????') {
                    $permanentTreatments++;
                } elseif ($val == '?????????? ??????????') {
                    $chronicDiseases++;
                } elseif ($val == '?????????????????? ??????????') {
                    $HHB++;
                } elseif ($val == '?????????????????? ??????????') {
                    $LHB++;
                } elseif ($val == '?????? ???? ??????????') {
                    $highBlood++;
                } elseif ($val == '?????? ???? ??????????') {
                    $lowBlood++;
                } elseif ($val == '???????????? ?????????? ????????') {
                    $usesAntibiotics++;
                } elseif ($val == '???????? ??????????') {
                    $lowWeight++;
                } elseif ($val == '?????????? ?????? ???? 18') {
                    $lessThan18++;
                } elseif ($val == '?????? ??????') {
                    $ToothExtraction++;
                } elseif ($val == '?????????? ????????') {
                    $others++;
                }
            }
        }
        return view('invoices.exclusion-from-the-doctor', compact('count', 'highBlood', 'lowBlood', 'ToothExtraction', 'lowWeight', 'permanentTreatments', 'chronicDiseases', 'HHB', 'LHB', 'others', 'lessThan18', 'usesAntibiotics'));
    }

    public function polcythemiasrReport(Request $request)
    {
        $economyIn = Polycythemia::where('type', '??????????????')->whereBetween('created_at', [$request->form_date, $request->to_date])->count();
        $economyOut = Polycythemia::where('type', '??????????')->whereBetween('created_at', [$request->form_date, $request->to_date])->count();
        $economyInBYMonth = Polycythemia::where('type', '??????????????')->whereMonth('created_at', now()->month)->count();
        $economyOutBYMonth = Polycythemia::where('type', '??????????')->whereMonth('created_at', now()->month)->count();
        return view('invoices.polcythemias-report', compact('economyIn', 'economyOut', 'economyInBYMonth', 'economyOutBYMonth'));
    }

    public function BloodDischarged(Request $request)
    {
        $list = ['A+' => 0, 'A-' => 0, 'B+' => 0, 'B-' => 0, 'AB+' => 0, 'AB-' => 0, 'O+' => 0, 'O-' => 0];
        $unitsList = ['?????????????? ??????????????' => 0, '?????????????? ??????????????' => 0, '??????????????' => 0, '??????????????' => 0, '??????????????' => 0, '????????????????' => 0, '??????????????' => 0, '??????????????' => 0, '??????????????' => 0, '????????????' => 0, '??????????' => 0, '????????????' => 0, '?????????????? ??????????????' => 0, '?????????? ????????' => 0, '??????????????' => 0, '?????????? ???????????? ????????????????' => 0, '?????? ????????????' => 0, '???????? ????????????' => 0];
        $count = 0;
        $unitCount = 0;

        $orderIds = DB::table('exchanges')->where('type', '??????????')->whereBetween('created_at', [$request->form_date, $request->to_date])->pluck('order_id');

        foreach ($orderIds as $orderId) {
            $order = Order::where('id', $orderId)->first();
            if ($order->person->blood_group == "A+") {
                $list['A+']++;
                $count++;
            } elseif ($order->person->blood_group == "A-") {
                $list['A-']++;
                $count++;
            } elseif ($order->person->blood_group == "B+") {
                $list['B+']++;
                $count++;
            } elseif ($order->person->blood_group == "B-") {
                $list['B-']++;
                $count++;
            } elseif ($order->person->blood_group == "AB-") {
                $list['AB-']++;
                $count++;
            } elseif ($order->person->blood_group == "AB+") {
                $list['AB+']++;
                $count++;
            } elseif ($order->person->blood_group == "O-") {
                $list['O-']++;
                $count++;
            } elseif ($order->person->blood_group == "O+") {
                $list['O+']++;
                $count++;
            }
        }
        foreach ($orderIds as $orderId) {
            $order = Order::where('id', $orderId)->first();
            if ($order->unit == '?????????????? ??????????????') {
                $unitsList['?????????????? ??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '?????????????? ??????????????') {
                $unitsList['?????????????? ??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '????????????????') {
                $unitsList['????????????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '????????????') {
                $unitsList['????????????']++;
                $unitCount++;
            } elseif ($order->unit == "??????????") {
                $unitsList['??????????']++;
                $unitCount++;
            } elseif ($order->unit == '????????????') {
                $unitsList['????????????']++;
                $unitCount++;
            } elseif ($order->unit == '?????????????? ??????????????') {
                $unitsList['?????????????? ??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '?????????? ????????') {
                $unitsList['?????????? ????????']++;
                $unitCount++;
            } elseif ($order->unit == '??????????????') {
                $unitsList['??????????????']++;
                $unitCount++;
            } elseif ($order->unit == '?????????? ???????????? ????????????????') {
                $unitsList['?????????? ???????????? ????????????????']++;
                $unitCount++;
            } elseif ($order->unit == '?????? ????????????') {
                $unitsList['?????? ????????????']++;
                $unitCount++;
            } elseif ($order->unit == '???????? ????????????') {
                $unitsList['???????? ????????????']++;
                $unitCount++;
            }
        }

        return view('invoices.BloodDischarged', compact('list', 'count', 'unitsList', 'unitCount'));
    }

    public function kidInvoice($id)
    {
        $kid = Kid::with(['person', 'bloodTest', 'motherBloodTest', 'ictTest', 'dctTest'])->find($id);

        $barcode = (string)$kid->id;
        return view('invoices.kidInvoice', compact('kid', 'barcode'));
    }

    public function investigationsInvoice($id)
    {
        $investigation = Investigation::find($id)->whereHas('tests', function ($query) {
            $query->whereNotNull('result');
        })->first();

        // dd($investigation);
        return view('invoices.investigationInvoice', compact('investigation'));
    }
}
