<?php

namespace Kematjaya\ReportBuilderBundle\Form;

use Kematjaya\ReportBuilderBundle\Entity\ReportQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReportQueryType extends AbstractType
{
    public function __construct(private RoleHierarchyInterface $roleHierarchy)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'placeholder' => 'Contoh: Rekap Paket Per OPD',
                    'class' => 'form-control',
                ],
                'constraints' => [new NotBlank(message: 'Nama laporan wajib diisi')],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Deskripsi singkat laporan ini...',
                    'rows' => 2,
                    'class' => 'form-control',
                ],
            ])
            ->add('sqlQuery', TextareaType::class, [
                'label' => 'SQL Query',
                'attr' => [
                    'placeholder' => 'SELECT ...',
                    'rows' => 8,
                    'class' => 'form-control sql-editor',
                    'id' => 'sql-editor-textarea',
                ],
                'constraints' => [new NotBlank(message: 'Query SQL wajib diisi')],
            ])
            ->add('queryParams', TextareaType::class, [
                'label' => 'Query Params',
                'required' => false,
                'attr' => [
                    'placeholder' => '[{"name":"tahun","label":"Tahun","type":"integer","default":2024}]',
                    'rows' => 3,
                    'class' => 'form-control',
                    'id' => 'query-params-textarea',
                ],
            ])
            ->add('outputType', ChoiceType::class, [
                'label' => 'Output Type',
                'choices' => [
                    '📋 Tabel' => 'table',
                    '📊 Bar Chart' => 'bar',
                    '📈 Line Chart' => 'line',
                    '🥧 Pie Chart' => 'pie',
                    '💠 Scatter Chart' => 'scatter',
                ],
                'attr' => ['class' => 'form-select', 'id' => 'output-type-select', 'onchange' => 'return selectOutput()'],
            ])
            ->add('chartXColumn', TextType::class, [
                'label' => 'Chart X Column',
                'required' => false,
                'attr' => [
                    'placeholder' => 'nama_kolom',
                    'class' => 'form-control',
                    'id' => 'chart-x-column',
                ],
                'help' => 'Column name from Query for X Axis'
            ])
            ->add('chartYColumn', TextType::class, [
                'label' => 'Chart Y Column',
                'required' => false,
                'attr' => [
                    'placeholder' => 'nama_kolom',
                    'class' => 'form-control',
                    'id' => 'chart-y-column',
                ],
                'help' => 'Column name from Query for Y Axis (Numeric Only)',
            ])
            ->add('icon', TextType::class, [
                'label' => 'icon',
                'attr' => [
                    'placeholder' => 'fa fa-bar-chart',
                    'class' => 'form-control',
                ],
                'help' => 'Contoh: fa fa-bar-chart, fa fa-table, fa fa-pie-chart',
            ])
            ->add('isEnabled', CheckboxType::class, [
                'label' => 'Enable This Report ?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);

        $roles = $this->roleHierarchy->getReachableRoleNames(['ROLE_ADMINISTRATOR', 'ROLE_SUPER_USER']);
        $choices = [];
        foreach ($roles as $role) {
            if ($role === 'ROLE_USER') continue; // Optional: skip ROLE_USER if you want to consider it default
            $label = str_replace('ROLE_', '', $role);
            $label = str_replace('_', ' ', $label);
            $label = ucwords(strtolower($label));
            $choices[$label] = $role;
        }

        $builder->add('roles', ChoiceType::class, [
            'label' => 'Akses Role (Biarkan kosong untuk semua)',
            'choices' => $choices,
            'multiple' => true,
            'required' => false,
            'attr' => [
                'class' => 'form-select select2', // Asumsi menggunakan select2 jika multiple
                'data-placeholder' => 'Pilih Role...',
            ]
        ]);

        $builder->get("queryParams")->addViewTransformer(
            new CallbackTransformer(function ($value) {
                if (!$value) {
                    return null;
                }

                return json_encode($value);
                }, function ($value) {
                    if (!$value) {
                        return [];
                    }

                    return json_decode($value, true);
                }),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReportQuery::class,
        ]);
    }
}
