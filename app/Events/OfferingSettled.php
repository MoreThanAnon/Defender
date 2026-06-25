<?php
namespace App\Events;
use App\Models\Offering;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class OfferingSettled { use Dispatchable, SerializesModels; public function __construct(public Offering $offering) {} }
