<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;

class ContactDto 
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
    private string $lastname;

    #[Type('string')]
    #[NotBlank()]
    #[Regex('/^(MALE|FEMALE)+$/')]
    private string $sex;

    #[Type('integer')]
    #[Range(
        min: 18,
        max: 150,
        notInRangeMessage: 'Your age must be above {{ min }} and less than {{ max }}',
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

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLastname(string $lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setSex(string $sex)
    {
        $this->sex = $sex;
        return $this;
    }

    public function getSex(): string
    {
        return $this->sex;
    }

    public function setAge(int $age)
    {
        $this->age = $age;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setPhone(string $phone)
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

}