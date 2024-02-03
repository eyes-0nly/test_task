<?php

declare(strict_types=1);

namespace App\ValueObject;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;

class Contact 
{

    #[Type('string')]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your first name must be at least {{ limit }} characters long',
        maxMessage: 'Your first name cannot be longer than {{ limit }} characters',
    )]
    private string $name;

    #[Type('string')]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your last name must be at least {{ limit }} characters long',
        maxMessage: 'Your last name cannot be longer than {{ limit }} characters',
    )]
    private string $lastName;

    #[Type('string')]
    #[NotBlank()]
    #[Regex('/^(MALE|FEMALE)+$/')]
    private string $sex;

    #[Type('integer')]
    #[Range(
        notInRangeMessage: 'Your age must be above {{ min }} and less than {{ max }}',
        min: 18,
        max: 150,
    )]
    private int $age;

    #[Type('string')]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your phone must be at least {{ limit }} characters long',
        maxMessage: 'Your phone cannot be longer than {{ limit }} characters',
    )]
    #[Regex('/^[0-9+()\- ]+$/')]
    private string $phone;

    #[Email()]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your Email must be at least {{ limit }} characters long',
        maxMessage: 'Your Email cannot be longer than {{ limit }} characters',
    )]
    private string $email;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setSex(string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getSex(): string
    {
        return $this->sex;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

}