<?php

declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Models\ContactModel;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TaskModel;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Filters\CatalogsFilter;
use AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;
use AmoCRM\Models\CustomFields\EnumModel;
use AmoCRM\Collections\CustomFields\CustomFieldsCollection;
use AmoCRM\Models\CustomFields\SelectCustomFieldModel;
use AmoCRM\Models\CustomFields\NumericCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use App\Exception\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Response;

use DateTime;
use DateTimeZone;
use App\ValueObject\Contact;
use Exception;

class AmoApiContactService
{
    private const SEX_CODE = 'SEX';

    private const SEX_NAME = 'Пол';

    private const SEX_ENUM_MALE = 'MALE';

    private const SEX_ENUM_MALE_NAME = 'МУЖЧИНА';

    private const SEX_ENUM_FEMALE = 'FEMALE';

    private const SEX_ENUM_FEMALE_NAME = 'ЖЕНЩИНА';

    private const AGE_CODE = 'AGE';

    private const AGE_NAME = 'Возраст';

    private const ENUM_WORK = 'WORK';

    private const PHONE_CODE = 'PHONE';

    private const EMAIL_CODE = 'EMAIL';

    private const TASK_TEXT = 'Позвонить по новому лиду из формы';

    private const WORK_HOURS = 32400;

    private AmoCRMApiClient $apiClient;

    public function setClient(AmoCRMApiClient $apiClient): AmoApiContactService
    {
        $this->apiClient = $apiClient;

        return $this;
    }

    public function checkIfCustomFieldsExists(): void
    {
        $customFieldsService = $this->apiClient->customFields(
            EntityTypesInterface::CONTACTS
        );

        $fields = $customFieldsService->get();
        $customFieldsCollection = new CustomFieldsCollection();

        //Проверяем, есть ли поля, если нет создаем
        if ($fields->getBy('code', self::SEX_CODE) === null) {
            $sex = new SelectCustomFieldModel();
            $sex->setName(self::SEX_NAME)
                ->setSort(30)
                ->setCode(self::SEX_CODE)
                ->setEnums(
                    (new CustomFieldEnumsCollection())
                        ->add(
                            (new EnumModel())
                                ->setValue(self::SEX_ENUM_MALE_NAME)
                                ->setCode(self::SEX_ENUM_MALE)
                                ->setSort(10)
                        )
                        ->add(
                            (new EnumModel())
                                ->setValue(self::SEX_ENUM_FEMALE_NAME)
                                ->setCode(self::SEX_ENUM_FEMALE)
                                ->setSort(20)
                        )
                );

            $customFieldsCollection->add($sex);
        }

        if ($fields->getBy('code', self::AGE_CODE) === null) {
            $age = new NumericCustomFieldModel();
            $age->setName(self::AGE_NAME)
                ->setSort(40)
                ->setCode(self::AGE_CODE);

            $customFieldsCollection->add($age);
        }

        if (!$customFieldsCollection->isEmpty()) {
            $customFieldsService->add($customFieldsCollection);
        }
    }

    public function getContactId(Contact $contact): int|null
    {
        try {
            $contacts = $this->apiClient
                ->contacts()
                ->get((new ContactsFilter())->setQuery($contact->getPhone()));
            if (is_null($contacts)) {
                throw EntityNotFoundException::create('No contacts found');
            }

            return $contacts->first()?->getId();
        } catch (AmoCRMApiException $e) {
            if ($e->getErrorCode() === Response::HTTP_NO_CONTENT) {
                return null;
            }
            
            throw $e;
        }
    }
    public function isContactHasSuccessfulLeads(Contact $contact): bool|null
    {
        try {
            $leads = $this->apiClient
                ->leads()
                ->get((new LeadsFilter())->setQuery($contact->getPhone()));

            return isset($leads) && $leads->getBy('statusId', LeadModel::WON_STATUS_ID);
        } catch (AmoCRMApiException $e) {
            if ($e->getErrorCode() === Response::HTTP_NO_CONTENT) {
                return null;
            }

            throw $e;
        }
    }

    public function sendLeadConnectedToContact(Contact $contact): void
    {
        //Создаем модель контакта
        $contactModel = new ContactModel();
        $contactModel
            ->setName(sprintf('%s %s', $contact->getName(), $contact->getLastName()))
            ->setFirstName($contact->getName())
            ->setLastName($contact->getLastName());

        //Добавляем значение кастомных полей в модель
        $contactModel->setCustomFieldsValues(
            (new CustomFieldsValuesCollection())
                ->add(
                    (new MultitextCustomFieldValuesModel())
                        ->setFieldCode(self::PHONE_CODE)
                        ->setValues(
                            (new MultitextCustomFieldValueCollection())->add(
                                (new MultitextCustomFieldValueModel())
                                    ->setEnum(self::ENUM_WORK)
                                    ->setValue($contact->getPhone())
                            )
                        )
                )
                ->add(
                    (new SelectCustomFieldValuesModel())
                        ->setFieldCode(self::SEX_CODE)
                        ->setValues(
                            (new SelectCustomFieldValueCollection())->add(
                                (new SelectCustomFieldValueModel())->setEnumCode(
                                    $contact->getSex()
                                )
                            )
                        )
                )
                ->add(
                    (new NumericCustomFieldValuesModel())
                        ->setFieldCode(self::AGE_CODE)
                        ->setValues(
                            (new NumericCustomFieldValueCollection())->add(
                                (new NumericCustomFieldValueModel())->setValue(
                                    $contact->getAge()
                                )
                            )
                        )
                )
                ->add(
                    (new MultitextCustomFieldValuesModel())
                        ->setFieldCode(self::EMAIL_CODE)
                        ->setValues(
                            (new MultitextCustomFieldValueCollection())->add(
                                (new MultitextCustomFieldValueModel())
                                    ->setEnum(self::ENUM_WORK)
                                    ->setValue($contact->getEmail())
                            )
                        )
                )
        );

        //Выбираем рандомного пользователя
        $usersCollection = $this->apiClient->users()->get();
        if (is_null($usersCollection)) {
            throw EntityNotFoundException::create('No users found');
        }

        $randomUser = $usersCollection->offsetGet(random_int(0, $usersCollection->count() - 1));
        if (is_null($randomUser)) {
            throw EntityNotFoundException::create('Offset is not found in collection');
        }

        //Создаем сделку
        $lead = new LeadModel();
        $lead
            ->setResponsibleUserId($randomUser->getId())
            ->setContacts((new ContactsCollection())->add($contactModel));

        $lead = $this->apiClient->leads()->addOneComplex($lead);

        // Получаем список товаров
        try {
            $productsCatalog = $this->apiClient
                ->catalogs()
                ->get((new CatalogsFilter())->setType(EntityTypesInterface::PRODUCTS));

            if (is_null($productsCatalog)) {
                throw EntityNotFoundException::create('No catalogs found');
            }

            $products = $this->apiClient
                ->catalogElements($productsCatalog->first()?->getId())
                ->get();
            
            if (is_null($products)) {
                $products = [];
            }
        } catch (AmoCRMApiException $e) {
            if ($e->getErrorCode() === Response::HTTP_NO_CONTENT) {
                $products = [];
            }
        }

        //Привязываем два товара к сделке
        if (!$products->isEmpty()) {
            $links = new LinksCollection();
            $products = $products->chunk(2)[0] ?? [];

            foreach ($products as $product){
                $links->add($product);
            }

            if (!$links->isEmpty()) {
                $this->apiClient->leads()->link($lead, $links);
            }
        }

        //Добавляем задачу
        $this->addTask($lead, $randomUser->getId());

    }

    public function sendCustomer(int $contactId): void
    {
        //Создадим покупателя
        $customer = new CustomerModel();

        $customer = $this->apiClient->customers()->addOne($customer);

        //Привяжем контакт к созданному покупателю
        $contact = (new ContactModel())
            ->setId($contactId);

        $links = (new LinksCollection())->add($contact);

        $this->apiClient->customers()->link($customer, $links);
    }

    public function addTask(LeadModel $lead, int $userId): void {
        //Добавим задачу ответственному
        $task = new TaskModel();

        //Устанавливаем время для задачи (+4 дня или до понедельника)
        $tz = new DateTimeZone('Europe/Moscow');
        $date = new DateTime();
        $date->setTimezone($tz);
        $date->modify('+5 day');
        $date->setTime(9, 0, 0, 0);
        $dayOfTheWeek = $date->format('N');
        if ($dayOfTheWeek === '6') {
            $date->modify('+2 day');
        } elseif ($dayOfTheWeek === '7') {
            $date->modify('+1 day');
        }
        
        $task
            ->setTaskTypeId(TaskModel::TASK_TYPE_ID_FOLLOW_UP)
            ->setText(self::TASK_TEXT)
            ->setCompleteTill((int) $date->format('U'))
            ->setEntityType(EntityTypesInterface::LEADS)
            ->setEntityId($lead->getId())
            ->setDuration(self::WORK_HOURS) //в течение рабочего дня
            ->setResponsibleUserId($userId);
        
            $this->apiClient->tasks()->addOne($task);
    }

}
