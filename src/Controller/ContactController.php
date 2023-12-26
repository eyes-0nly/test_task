<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Dto\ContactRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContactController extends AbstractController
{
    #[Route('/contact/form', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {

        return $this->render('contact/index.html.twig', [
            'controller_name' => 'ContactController',
        ]);
    }

    #[Route('/contact/add', name: 'app_contact_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator): Response
    {
        $parameters = [];
        if ($content = $request->getContent()) {
            $parameters = json_decode($content, true);
            if (isset($parameters['name']) 
            and isset($parameters['lastname']) 
            and isset($parameters['sex'])
            and isset($parameters['phone'])
            and isset($parameters['email'])) {

                $contact = new ContactRequest();
                $contact->name = $parameters['name'];
                $contact->lastname = $parameters['lastname'];
                $contact->sex = (int)$parameters['sex'];
                $contact->phone = $parameters['phone'];
                $contact->email = $parameters['email'];
                $errors = $validator->validate($contact);

                if (count($errors) > 0) {
                    $errorsString = (string) $errors;
                    return new JsonResponse(array('status' => 'error', 'message' => $errorsString));
                } else {
                    return new JsonResponse(array('status' => 'ok', 'contact' => $contact));
                }

            } else {
                return new JsonResponse(array('status' => 'error'));
            }
        }

    }
}
