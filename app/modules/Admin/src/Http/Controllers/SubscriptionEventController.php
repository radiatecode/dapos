<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\DataTables\SubscriptionEventsDataTable;

class SubscriptionEventController extends Controller
{
    public function index(SubscriptionEventsDataTable $dataTable)
    {
        return $dataTable->render('admin::app.subscription-events.index');
    }
}
