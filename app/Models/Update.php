<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//I'm sure I'll regret this name later but for now it's fine
//This is for the updates on the property
//Update for example would be property 1 is now bought, we'd add an update that shows on the page
// If theres a new documents we should show or our bid was succefful, we'd add another update to the front end.

class Update extends Model
{
    use HasFactory;

    // Specify which fields are mass assignable
    protected $fillable = [
        'property_id',
        'title',
        'content',
        'image',
    ];

    // Optional: define relationship to Property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
