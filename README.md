- report builder for symfony 7
- installation
  ```
  composer require kematjaya/report-builder-bundle
  ```
- add to bundles.php
  ```
  Kematjaya\ReportBuilderBundle\ReportBuilderBundle::class => ['all' => true]
  ```
- add to routes.yaml
  ```
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
  ```
  php bin/console doctrine:schema:update --force
  ```
- access with url: {{ base-url }} /report-builder.html
