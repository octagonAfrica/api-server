<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'ID' => $this->m_id,
            'ClientID' => $this->m_number,
            'Code' => $this->m_combined,
            'name' => $this->m_name,
            'id_number' => $this->m_id_number,
            'kra_pin' => $this->m_pin,
            'payment_mode' => $this->m_payment_mode
        ];
    }
}
