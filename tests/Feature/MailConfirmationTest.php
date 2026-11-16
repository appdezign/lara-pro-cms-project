<?php

namespace Tests\Feature;

use Lara\Front\Mail\MailConfirmation;
use stdClass;
use Tests\TestCase;

/**
 * Form mail uses the form's own mail view, or the shared form template when the
 * theme has no view for that form.
 */
class MailConfirmationTest extends TestCase
{
    public function test_a_form_with_its_own_mail_view_uses_it(): void
    {
        $mail = new MailConfirmation($this->maildata('email.contactforms.webmaster'));

        $mail->render();

        $this->assertSame('email.contactforms.webmaster', $mail->maildata->view);
    }

    public function test_a_form_without_a_mail_view_uses_the_form_template(): void
    {
        $mail = new MailConfirmation($this->maildata('email.zznosuchforms.confirm'));

        $html = $mail->render();

        $this->assertSame('email._templates.form.confirm', $mail->maildata->view);
        $this->assertStringContainsString('Test field value', $html);
    }

    private function maildata(string $view): object
    {
        $maildata = new stdClass;
        $maildata->view = $view;
        $maildata->subject = 'Test subject';

        $maildata->from = new stdClass;
        $maildata->from->email = 'noreply@example.com';
        $maildata->from->name = 'Example';

        $maildata->style = json_decode(json_encode(config('lara-front.mail')), false);

        $maildata->company = (object) [
            'company_name' => 'Example',
            'company_street' => 'Street',
            'company_street_nr' => '1',
            'company_pcode' => '1234 AB',
            'company_city' => 'City',
            'company_telephone' => '0123456789',
        ];

        $maildata->content = new stdClass;
        $maildata->content->title = 'Test title';
        $maildata->content->lead = 'Test lead';
        $maildata->content->body = 'Test body';
        $maildata->content->data = new stdClass;
        $maildata->content->data->name = ['colname' => 'Name', 'colval' => 'Test field value'];

        return $maildata;
    }
}
