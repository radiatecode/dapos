<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\DataTables\PaymentsDataTable;

class PaymentController extends Controller
{
    public function index(PaymentsDataTable $dataTable)
    {
        return $dataTable->render('admin::app.payments.index');
    }
}
