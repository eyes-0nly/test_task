<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Dto\ContactDto;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\AmoApiAuthDirector;
use App\Service\AmoApiContactService;

class ContactController extends AbstractController
{
    private const FORM_TEMPLATE = 'contact/index.html.twig';

    #[Route('/contact/form', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render(self::FORM_TEMPLATE);
    }

    #[Route('/contact/add', name: 'app_contact_add', methods: ['POST'])]
    public function add(
        Request $request,
        ValidatorInterface $validator,
        AmoApiAuthDirector $authDirector,
        AmoApiContactService $contactService
    ): Response {
        $parameters = [];
        if ($content = $request->getContent()) {
            $parameters = json_decode($content, true);
            if (
                isset($parameters['name']) &&
                isset($parameters['lastname']) &&
                isset($parameters['sex']) &&
                isset($parameters['age']) &&
                isset($parameters['phone']) &&
                isset($parameters['email'])
            ) {
                $contact = new ContactDto();
                $contact->name = $parameters['name'];
                $contact->lastname = $parameters['lastname'];
                $contact->sex = $parameters['sex'];
                $contact->age = (int) $parameters['age'];
                $contact->phone = $parameters['phone'];
                $contact->email = $parameters['email'];
                $errors = $validator->validate($contact);

                //Проверяем валидацию контакта
                if (count($errors) > 0) {
                    $errorsString = (string) $errors;
                    return new JsonResponse([
                        'status' => 'error',
                        'msg' => $errorsString,
                    ]);
                } else {
                    $apiClient = $authDirector
                        ->setTokenPath('../amo_token.json')
                        ->buildAuthentication()
                        ->getAuthenticatedClient();
                    $contactService = $contactService->setClient($apiClient);
                    $contactService->checkIfCustomFieldsExists();
                    $contactId = $contactService->searchContact($contact);
                    if (
                        $contactId !== 0 &&
                        $contactService->searchContactLeads($contact) === false
                    ) {
                        $contactService->sendCustomer($contact, $contactId);
                        return new JsonResponse([
                            'status' => 'ok',
                            'msg' => 'Added customer',
                        ]);
                    } else {
                        $contactService->sendLead($contact);
                        return new JsonResponse([
                            'status' => 'ok',
                            'msg' => 'Added lead',
                        ]);
                    }
                }
            } else {
                return new JsonResponse([
                    'status' => 'error',
                    'msg' => 'Not enough parameters in json form data',
                ]);
            }
        } else {
            return new JsonResponse([
                'status' => 'error',
                'msg' => 'No parameters in request',
            ]);
        }
    }
}
