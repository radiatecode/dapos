<?php

use DA\Admin\Enums\AdminPermission;

describe('index', function () {
    it('redirects guests from the payment list to login', function () {
        $this->get(route('admin.payments.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without payments.view', function () {
        actingAsPlatformAdmin([AdminPermission::InvoicesView]);

        $this->get(route('admin.payments.index'))->assertForbidden();
    });

    it('renders the payment datatable for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('payments-table', false)
            ->assertSee('Payments');
    });
});
