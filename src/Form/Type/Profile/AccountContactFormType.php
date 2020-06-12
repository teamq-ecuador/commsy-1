<?php


namespace App\Form\Type\Profile;


use App\Form\Type\CheckedFileType;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountContactFormType extends AbstractType
{

    private $em;
    private $legacyEnvironment;

    private $userItem;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $uploadErrorMessage = $this->translator->trans('upload error', [], 'error');
        $noFileIdsMessage = $this->translator->trans('upload error', [], 'error');

        $builder
            ->add('subject', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'Subject',
                'translation_domain' => 'mail',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Subject',
                ],
            ])
            ->add('message', CKEditorType::class, [
                'label' => false,
                'translation_domain' => 'form',
                'required' => true,
                'config_name' => 'cs_mail_config',
            ])
            ->add('recipient', TextType::class, [
                'label' => 'Additional recipients',
                'translation_domain' => 'mail',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Additional recipients',
                ],
            ])
            ->add('autoSaveStatus', ChoiceType::class, [
                'label' => 'Copy to sender',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
                'translation_domain' => 'mail',
                'choice_translation_domain' => 'form',
                'required' => true,
            ])
            ->add('upload', FileType::class, [
                'attr' => [
                    'data-uk-csupload' => '{"path": "' . $options['uploadUrl'] . '", "errorMessage": "'.$uploadErrorMessage.'", "noFileIdsMessage": "'.$noFileIdsMessage.'"}',
                ],
                'required' => false,
                'multiple' => true,
                'label' => 'Attachments',
                'translation_domain' => 'mail',
            ])
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => CheckedFileType::class,
                'entry_options' => [
                ],
                'label' => false,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'Send',
                'translation_domain' => 'mail',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => 'formnovalidate',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['item', 'uploadUrl'])
            ->setAllowedTypes('item', 'cs_item')
            ->setAllowedTypes('uploadUrl', 'string')
            ->setDefaults([
                'users' => [],
                'item' => null,
                'copy_to_sender' => false,
                'translation_domain' => 'profile',
            ])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'room_profile_contact';
    }
}