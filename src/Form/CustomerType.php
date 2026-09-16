<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Partner;
use App\Entity\TariffZone;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CustomerType extends AbstractType
{
    private const REQUIRED_FIELD_MESSAGE = 'Ce champ ne peut pas être vide.';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank([
                        'message' => self::REQUIRED_FIELD_MESSAGE,
                    ]),
                    new Regex([
                        'pattern' => '/^[\p{L}0-9\s\'-]*$/u',
                        'message' => 'Le champ ne doit contenir que des lettres, des chiffres, des espaces, des apostrophes et des tirets.',
                    ]),
                ],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIREN ou SIRET',
                'required' => false,
                'constraints' => [
                    new Callback([
                        'callback' => static function (
                            mixed $numSirenSiret,
                            ExecutionContextInterface $context,
                        ): void {
                            if (empty($numSirenSiret)) {
                                return;
                            }

                            $numSirenSiret = str_replace(
                                ' ',
                                '',
                                (string) $numSirenSiret
                            );

                            if (!preg_match('/^(?:\d{9}|\d{14})$/', $numSirenSiret)) {
                                $context->addViolation(
                                    'Le numéro SIREN/SIRET n\'est pas valide.'
                                );
                            }
                        },
                    ]),
                ],
                'attr' => [
                    'oninput' => "this.value=this.value.replace(/[^0-9 ]+/g,'').replace(/^(\\d{3}) ?(\\d{3}) ?(\\d{3}) ?(\\d{0,5}).*/, '$1 $2 $3 $4').trim()",
                    'maxlength' => '18',
                    'inputmode' => 'numeric',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank([
                        'message' => self::REQUIRED_FIELD_MESSAGE,
                    ]),
                    new Regex([
                        'pattern' => '/^[\p{L}0-9\s\'-]*$/u',
                        'message' => 'Ce champ ne peut contenir que des lettres, des chiffres, des espaces, des tirets et des apostrophes.',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Votre saisie est trop longue.',
                    ]),
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9]{4,5}$/',
                        'message' => 'Le code postal doit comporter entre 4 et 5 chiffres.',
                    ]),
                    new NotBlank([
                        'message' => self::REQUIRED_FIELD_MESSAGE,
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new NotBlank([
                        'message' => self::REQUIRED_FIELD_MESSAGE,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*\p{L})[\p{L}0-9\s\'-]*$/u',
                        'message' => 'Votre saisie semble incorrecte.',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Votre saisie est trop longue.',
                    ]),
                ],
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'constraints' => [
                    new NotBlank([
                        'message' => self::REQUIRED_FIELD_MESSAGE,
                    ]),
                ],
                'empty_data' => 'FR',
            ])
            ->add('isProfessional', CheckboxType::class, [
                'label' => 'Client professionnel',
                'required' => false,
            ])
            ->add('isPartner', CheckboxType::class, [
                'label' => 'Client partenaire',
                'required' => false,
            ])
            ->add('partner', EntityType::class, [
                'label' => 'Partenariat :',
                'class' => Partner::class,
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'empty_data' => null,
                'constraints' => [
                    new Callback(
                        static function (
                            mixed $partner,
                            ExecutionContextInterface $context,
                        ): void {
                            $form = $context->getRoot();
                            $isPartner = $form->get('isPartner')->getData();

                            if ($isPartner && empty($partner)) {
                                $context
                                    ->buildViolation(
                                        'Vous devez sélectionner un partenaire.'
                                    )
                                    ->atPath('partner')
                                    ->addViolation();
                            }
                        }
                    ),
                ],
            ])
            ->add('tariffZone', EntityType::class, [
                'label' => 'Zone tarifaire :',
                'class' => TariffZone::class,
                'required' => true,
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn-alice-form',
                ],
            ])
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                self::handlePreSubmit(...)
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }

    private static function handlePreSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (!is_array($data)) {
            return;
        }

        $isPartner = $data['isPartner'] ?? false;
        $isProfessional = $data['isProfessional'] ?? false;

        if (!$isPartner) {
            $data['partner'] = null;
        }

        if (!$isProfessional) {
            $data['siret'] = null;
        }

        $event->setData($data);
    }
}
