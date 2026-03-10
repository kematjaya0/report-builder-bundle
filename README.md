# Report Builder for Symfony 7
- installation
  ```bash
  composer require kematjaya/report-builder-bundle
  ```
- add to bundles.php
  ```php
  Kematjaya\ReportBuilderBundle\ReportBuilderBundle::class => ['all' => true]
  ```
- add to routes.yaml
  ```yaml
  report_builder:
    resource: '@ReportBuilderBundle/Resources/config/routes.yaml'
  ```
  available routes:
  ```
  - report_builder_index
  - report_builder_new
  - report_builder_edit
  - report_builder_delete
  - report_builder_preview
  - report_builder_db_structure
  - report_builder_view
  - report_builder_export_csv
  ```
- update database schema
  ```bash
  php bin/console doctrine:schema:update --force
  ```
- access with url: {{ base-url }} /report-builder.html
- show report as menu:
  ```html
  report_saved_menus() // in twig file
  ```
