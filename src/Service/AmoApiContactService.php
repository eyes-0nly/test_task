<?php

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Models\ContactModel;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Collections\TasksCollection;
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
use AmoCRM\Filters\CustomFieldsFilter;
use AmoCRM\Models\CustomFields\CustomFieldModel;

use DateTime;
use DateTimeZone;
use App\Dto\ContactDto;

class AmoApiContactService
{
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
        if (empty($fields->getBy("code", "SEX"))) {
            $sex = new SelectCustomFieldModel();
            $sex->setName("Пол")
                ->setSort(30)
                ->setCode("SEX")
                ->setEnums(
                    (new CustomFieldEnumsCollection())
                        ->add(
                            (new EnumModel())
                                ->setValue("МУЖЧИНА")
                                ->setCode("MALE")
                                ->setSort(10)
                        )
                        ->add(
                            (new EnumModel())
                                ->setValue("ЖЕНЩИНА")
                                ->setCode("FEMALE")
                                ->setSort(20)
                        )
                );

            $customFieldsCollection->add($sex);
        }

        if (empty($fields->getBy("code", "AGE"))) {
            $age = new NumericCustomFieldModel();
            $age->setName("Возраст")
                ->setSort(40)
                ->setCode("AGE");

            $customFieldsCollection->add($age);
        }

        if ($customFieldsCollection->toArray()) {
            $customFieldsCollection = $customFieldsService->add(
                $customFieldsCollection
            );
        }
    }

    public function searchContact(ContactDto $contactDto): int
    {
        try {
            $contacts = $this->apiClient
                ->contacts()
                ->get((new ContactsFilter())->setQuery($contactDto->phone));
            return $contacts->first()->getId();
        } catch (AmoCRMApiException $e) {
            if ($e->getMessage() === "No content") {
                return 0;
            }
        }
    }

    public function searchContactLeads(ContactDto $contactDto): bool
    {
        try {
            $filter = (new LeadsFilter())->setQuery($contactDto->phone);

            $leads = $this->apiClient->leads()->get($filter);

            if (isset($leads)) {
                $leads = $leads->getBy("statusId", LeadModel::WON_STATUS_ID);
                if ($leads) {
                    return false;
                } else {
                    return true;
                }
            }
        } catch (AmoCRMApiException $e) {
            if ($e->getMessage() === "No content") {
                return true;
            }
        }
    }

    public function sendLead(ContactDto $contactDto): void
    {
        //Создаем модель контакта
        $contact = new ContactModel();
        $contact
            ->setName($contactDto->name . " " . $contactDto->lastname)
            ->setFirstName($contactDto->name)
            ->setLastName($contactDto->lastname);

        //Добавляем значение кастомных полей в модель
        $contact->setCustomFieldsValues(
            (new CustomFieldsValuesCollection())
                ->add(
                    (new MultitextCustomFieldValuesModel())
                        ->setFieldCode("PHONE")
                        ->setValues(
                            (new MultitextCustomFieldValueCollection())->add(
                                (new MultitextCustomFieldValueModel())
                                    ->setEnum("WORK")
                                    ->setValue($contactDto->phone)
                            )
                        )
                )
                ->add(
                    (new SelectCustomFieldValuesModel())
                        ->setFieldCode("SEX")
                        ->setValues(
                            (new SelectCustomFieldValueCollection())->add(
                                (new SelectCustomFieldValueModel())->setEnumCode(
                                    $contactDto->sex
                                )
                            )
                        )
                )
                ->add(
                    (new NumericCustomFieldValuesModel())
                        ->setFieldCode("AGE")
                        ->setValues(
                            (new NumericCustomFieldValueCollection())->add(
                                (new NumericCustomFieldValueModel())->setValue(
                                    $contactDto->age
                                )
                            )
                        )
                )
                ->add(
                    (new MultitextCustomFieldValuesModel())
                        ->setFieldCode("EMAIL")
                        ->setValues(
                            (new MultitextCustomFieldValueCollection())->add(
                                (new MultitextCustomFieldValueModel())
                                    ->setEnum("WORK")
                                    ->setValue($contactDto->email)
                            )
                        )
                )
        );

        //Создаем контакт
        $contact = $this->apiClient->contacts()->addOne($contact);

        //Добавим компанию для сделки
        try {
            $company = $this->apiClient
                ->companies()
                ->get()
                ->first();
        } catch (AmoCRMApiException $e) {
            $company = new CompanyModel();
            $company->setName("Компания " . rand(1, 1000));
            $company = $this->apiClient->companies()->addOne($company);
        }

        //Выбираем рандомного пользователя
        $usersCollection = $this->apiClient->users()->get();
        $users = $usersCollection->toArray();
        $random_user = $users[array_rand($users)];

        //Создаем сделку
        $lead = new LeadModel();
        $lead
            ->setName("Сделка из формы")
            ->setPrice(100000)
            ->setResponsibleUserId($random_user["id"])
            ->setContacts((new ContactsCollection())->add($contact))
            ->setCompany($company);
        $lead = $this->apiClient->leads()->addOne($lead);

        // Получаем список товаров
        try {
            $productsCatalog = $this->apiClient
                ->catalogs()
                ->get((new CatalogsFilter())->setType("products"));
            $products = $this->apiClient
                ->catalogElements($productsCatalog->first()->getId())
                ->get();
        } catch (Exception $e) {
            $products = [];
        }

        //Привязываем два товара к сделке
        if ($products) {
            $links = new LinksCollection();

            for ($i = 0; $i < 2; $i++) {
                $product = $products[$i];
                $links->add($product);
            }

            $this->apiClient->leads()->link($lead, $links);
        }

        //Добавим задачу отвественному
        $task = new TaskModel();

        //Устанавливаем время для задачи (+4 дня или до понедельника)
        $tz = new DateTimeZone("Europe/Moscow");
        $date = new DateTime("now");
        $date->setTimezone($tz);
        $date->modify("+5 day");
        $date->setTime(9, 0, 0, 0);
        $day_of_the_week = $date->format("l");
        if ($day_of_the_week == "Saturday") {
            $date->modify("+2 day");
        } elseif ($day_of_the_week == "Sunday") {
            $date->modify("+1 day");
        }

        $task
            ->setTaskTypeId(TaskModel::TASK_TYPE_ID_FOLLOW_UP)
            ->setText("Позвонить по новому лиду из формы")
            ->setCompleteTill($date->format("U"))
            ->setEntityType(EntityTypesInterface::LEADS)
            ->setEntityId($lead->getId())
            ->setDuration(60 * 60 * 9) //в течение рабочего дня
            ->setResponsibleUserId($random_user["id"]);

        $this->apiClient->tasks()->addOne($task);
    }

    public function sendCustomer(ContactDto $contactDto, int $contactId): void
    {
        //Создадим покупателя
        $customer = new CustomerModel();

        $customer = $this->apiClient->customers()->addOne($customer);

        //Привяжем контакт к созданному покупателю
        $contact = $this->apiClient->contacts()->getOne($contactId);
        $contact->setIsMain(false);

        $links = new LinksCollection();
        $links->add($contact);

        $this->apiClient->customers()->link($customer, $links);
    }
}