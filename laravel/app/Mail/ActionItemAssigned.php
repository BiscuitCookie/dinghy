<?php

namespace App\Mail;

use App\Models\ActionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActionItemAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ActionItem $item)
    {
        $this->onQueue('mail');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новое действие после ретро: '.$this->item->retro->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.action-item-assigned',
        );
    }
}
