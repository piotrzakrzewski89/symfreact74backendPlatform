<?php

namespace App\Application\Service;

use App\Domain\Entity\ShippingAddress;
use App\Domain\Repository\ShippingAddressRepository;
use Symfony\Component\Uid\Uuid;

class ShippingAddressService
{
    public function __construct(
        private readonly ShippingAddressRepository $addressRepository
    ) {
    }

    /**
     * Create a new shipping address
     */
    public function createAddress(array $data): ShippingAddress
    {
        $address = new ShippingAddress();
        
        $address->setUserUuid(Uuid::fromString($data['userUuid']));
        $address->setLabel($data['label']);
        $address->setFirstName($data['firstName']);
        $address->setLastName($data['lastName']);
        $address->setEmail($data['email']);
        $address->setPhone($data['phone'] ?? null);
        $address->setAddress($data['address']);
        $address->setCity($data['city']);
        $address->setPostalCode($data['postalCode']);
        $address->setCountry($data['country'] ?? 'Polska');
        
        // If this is the first address or explicitly marked as default, set as default
        if ($data['isDefault'] ?? false) {
            $this->setAsDefault($address);
        } else {
            // Check if user has any addresses - if not, make this default
            $existingAddresses = $this->addressRepository->findByUserUuid($address->getUserUuid());
            if (empty($existingAddresses)) {
                $address->setIsDefault(true);
            }
        }

        $this->addressRepository->save($address, true);
        
        return $address;
    }

    /**
     * Update existing shipping address
     */
    public function updateAddress(Uuid $id, array $data): ShippingAddress
    {
        $address = $this->addressRepository->find($id);
        
        if (!$address) {
            throw new \RuntimeException('Address not found');
        }

        // Update fields
        if (isset($data['label'])) {
            $address->setLabel($data['label']);
        }
        if (isset($data['firstName'])) {
            $address->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $address->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            $address->setEmail($data['email']);
        }
        if (isset($data['phone'])) {
            $address->setPhone($data['phone']);
        }
        if (isset($data['address'])) {
            $address->setAddress($data['address']);
        }
        if (isset($data['city'])) {
            $address->setCity($data['city']);
        }
        if (isset($data['postalCode'])) {
            $address->setPostalCode($data['postalCode']);
        }
        if (isset($data['country'])) {
            $address->setCountry($data['country']);
        }
        
        // Handle default status
        if (isset($data['isDefault']) && $data['isDefault']) {
            $this->setAsDefault($address);
        }

        $address->setUpdatedAt(new \DateTimeImmutable());
        $this->addressRepository->save($address, true);
        
        return $address;
    }

    /**
     * Delete shipping address
     */
    public function deleteAddress(Uuid $id): void
    {
        $address = $this->addressRepository->find($id);
        
        if (!$address) {
            throw new \RuntimeException('Address not found');
        }

        // If this was the default address, set another as default if any exist
        if ($address->isDefault()) {
            $otherAddresses = $this->addressRepository->findByUserUuid($address->getUserUuid());
            $otherAddresses = array_filter($otherAddresses, fn($addr) => $addr->getId() !== $id);
            
            if (!empty($otherAddresses)) {
                $newDefault = reset($otherAddresses);
                $newDefault->setIsDefault(true);
                $this->addressRepository->save($newDefault);
            }
        }

        $this->addressRepository->remove($address, true);
    }

    /**
     * Get all addresses for user
     */
    public function getUserAddresses(Uuid $userUuid): array
    {
        return $this->addressRepository->findByUserUuid($userUuid);
    }

    /**
     * Get default address for user
     */
    public function getDefaultAddress(Uuid $userUuid): ?ShippingAddress
    {
        return $this->addressRepository->findDefaultByUserUuid($userUuid);
    }

    /**
     * Set address as default (unset others)
     */
    private function setAsDefault(ShippingAddress $address): void
    {
        // Unset all other addresses for this user
        $this->addressRepository->createQueryBuilder('sa')
            ->update()
            ->set('sa.isDefault', 'false')
            ->where('sa.userUuid = :userUuid')
            ->andWhere('sa.id != :addressId')
            ->setParameter('userUuid', $address->getUserUuid()->toRfc4122())
            ->setParameter('addressId', $address->getId()->toRfc4122())
            ->getQuery()
            ->execute();

        // Set this address as default
        $address->setIsDefault(true);
    }

    /**
     * Convert address to array
     */
    public function addressToArray(ShippingAddress $address): array
    {
        return $address->toArray();
    }

    /**
     * Convert multiple addresses to arrays
     */
    public function addressesToArray(array $addresses): array
    {
        return array_map([$this, 'addressToArray'], $addresses);
    }

    /**
     * Validate address data
     */
    public function validateAddressData(array $data, ?Uuid $excludeId = null): array
    {
        $errors = [];

        // Required fields
        if (empty($data['label'])) {
            $errors['label'] = 'Label is required';
        }
        if (empty($data['firstName'])) {
            $errors['firstName'] = 'First name is required';
        }
        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Last name is required';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        if (empty($data['address'])) {
            $errors['address'] = 'Address is required';
        }
        if (empty($data['city'])) {
            $errors['city'] = 'City is required';
        }
        if (empty($data['postalCode'])) {
            $errors['postalCode'] = 'Postal code is required';
        } elseif (!preg_match('/^\d{2}-\d{3}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'Invalid postal code format (XX-XXX)';
        }

        // Optional phone validation
        if (!empty($data['phone'])) {
            if (!preg_match('/^[+]?[0-9\s\-()]{9,15}$/', $data['phone'])) {
                $errors['phone'] = 'Invalid phone format';
            }
        }

        return $errors;
    }
}
