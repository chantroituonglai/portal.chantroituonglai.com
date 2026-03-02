<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Deal_merge_fields extends App_merge_fields
{
    /**
     * This function builds an array of custom email templates keys.
     * The provided keys will be available in perfex email template editor for the supported templates.
     * @return array
     */
    public function build()
    {
        // List of email templates used by the plugin
        $templates = [
            'deal_send_email',
        ];
        $available = ['deal'];
        return [
            [
                'name' => 'Deal Subject',
                'key' => '{subject}', // Key for instance name
                'available' => $available,
                'templates' => $templates,
            ],
            [
                'name' => 'Deal Message',
                'key' => '{message}', // Key for instance name
                'available' => $available,
                'templates' => $templates,
            ],
        ];
    }

    /**
     * Format merge fields for company instance
     * @param object $deal
     * @return array
     */
    public function format($deal)
    {
        $fields = [];
        $fields['{subject}'] = $deal->subject;
        $fields['{message}'] = $deal->message;
        return $fields;
    }

}
