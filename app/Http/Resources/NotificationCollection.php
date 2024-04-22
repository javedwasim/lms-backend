<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PropertyCollection;
use App\Models\Property;
use App\Models\PropertyImage;

class NotificationCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    
    public function toArray($request)
    {   
        return [
            'notification_id' => $this->id, 
            'header' => $this->header, 
            'message' => $this->message, 
            'create_date' => date('Y-m-d h:m:a',strtotime($this->created_at)), 
            'notification_type' => $this->notification_type,  // 1=property,2=like
        ];
    }
}
