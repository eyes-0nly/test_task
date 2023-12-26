<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;



class ContactRequest {
    #[Type('string')]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your first name must be at least {{ limit }} characters long',
        maxMessage: 'Your first name cannot be longer than {{ limit }} characters',
    )]
    public string $name;

    #[Type('string')]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your last name must be at least {{ limit }} characters long',
        maxMessage: 'Your last name cannot be longer than {{ limit }} characters',
    )]
    public string $lastname;

    #[Type('integer')]
    #[Assert\Range(
        min: 0,
        max: 1,
        notInRangeMessage: 'Invalid input. It should be 0 or 1. 0 - male, 1 - female.',
    )]
    public int $sex;

    #[Type('string')]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your phone must be at least {{ limit }} characters long',
        maxMessage: 'Your phone cannot be longer than {{ limit }} characters',
    )]
    #[Regex('/^[0-9+()\- ]+$/')]
    public string $phone;

    #[Email()]
    #[NotBlank()]
    #[Length(
        min: 2,
        max: 50,
        minMessage: 'Your Email must be at least {{ limit }} characters long',
        maxMessage: 'Your Email cannot be longer than {{ limit }} characters',
    )]
    public string $email;
}