<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\ValueObject\Contact;
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

    #[Route('/contacts', name: 'app_contact_add', methods: ['POST'])]
    public function add(
        Request $request,
        ValidatorInterface $validator,
        AmoApiAuthDirector $authDirector,
        AmoApiContactService $contactService
    ): Response {
        if ($content = $request->getContent()) {
            $parameters = json_decode($content, true);
            if (
                isset(
                    $parameters['name'],
                    $parameters['lastname'],
                    $parameters['sex'],
                    $parameters['age'],
                    $parameters['phone'],
                    $parameters['email']
                )
            ) {
                $contact = new Contact();
                $contact
                    ->setName($parameters['name'])
                    ->setLastname($parameters['lastname'])
                    ->setSex($parameters['sex'])
                    ->setAge((int) $parameters['age'])
                    ->setPhone($parameters['phone'])
                    ->setEmail($parameters['email']);
                $errors = $validator->validate($contact);

                //Проверяем валидацию контакта
                if ($errors->count() > 0) {
                    $errorsString = 'Validation errors: ';

                    foreach ($errors as $error) {
                        $errorsString .= sprintf('%s %s;', $error->getCode(), $error->getMessage());
                    }

                    return new JsonResponse([
                        'code' => Response::HTTP_BAD_REQUEST,
                        'msg' => $errorsString,
                    ]);
                }

                $apiClient = $authDirector
                    ->buildAuthentication()
                    ->getAuthenticatedClient();
                $contactService = $contactService->setClient($apiClient);
                $contactService->checkIfCustomFieldsExists();
                $contactId = $contactService->getContactId($contact);
                    
                if (
                    $contactId && $contactService->isContactHasSuccessfulLeads($contact)
                ) {
                    $contactService->sendCustomer($contactId);

                    return new JsonResponse([
                        'code' => Response::HTTP_OK,
                        'msg' => 'Added customer',
                    ]);
                }
                $contactService->sendLeadConnectedToContact($contact);

                return new JsonResponse([
                    'code' => Response::HTTP_OK,
                    'msg' => 'Added lead',
                ]);
            } else {
                return new JsonResponse([
                    'code' => Response::HTTP_BAD_REQUEST,
                    'msg' => 'Not enough parameters in json form data',
                ]);
            }
        } else {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'msg' => 'No parameters in request',
            ]);
        }
    }
}
