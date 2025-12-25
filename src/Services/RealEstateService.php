<?php

class RealEstateService {
    public function getByMember(array $member): array {
        // Получение рандомных данные
        if (rand(0, 10) > 7) {
            return ['hasRealEstate' => false, 'objects' => []];
        }

        return [
            'hasRealEstate' => true,
            'objects' => [
                [
                    'Type' => 'Квартира',
                    'Address' => 'ул. Ленина, д. ' . rand(1, 100),
                    'Ownership' => 'Долевая'
                ]
            ]
        ];
    }
}