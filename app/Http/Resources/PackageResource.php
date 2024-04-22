<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    const OneTime = 'onetime';
    const SubscriptionOneTime = 'subscription_onetime';
    const Subscription = 'subscription';
    const Free = 'free';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'package_title' => $this->package_title,
            'description' => $this->description,
            'packagetype' => $this->getDescription($this->packagetype),
            'packagemultiples' => $this->packagemultiples
        ];
    }

    public function getDescription($value)
    {
        switch ($value) {
            case self::OneTime:
                return 'One Time Specific Date';
            case self::SubscriptionOneTime:
                return 'One Time Specific Month';
            case self::Subscription:
                return 'Subscription';
            case self::Free:
                return 'Free';
            default:
                return '';
        }
    }
}
