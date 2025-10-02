<?php
require_once(EXTENSIONS . '/importcsv/lib/parsecsv-0.3.2/parsecsv.lib.php');

class contentExtensionImportcsvIndex extends AdministrationPage
{
/*
    public function __construct(&$parent)
    {
        parent::__construct($parent);

    }
*/

    public function build(array $context = array())
    {
        parent::build();
        parent::addStylesheetToHead(URL . '/extensions/importcsv/assets/importcsv.css');
        // parent::addStylesheetToHead(URL . '/symphony/assets/forms.css');
        parent::addScriptToHead(URL.'/extensions/importcsv/assets/importcsv.js', 70);
        $this->setTitle('Symphony - Import / export CSV');
        $this->Context->appendChild(new XMLElement('h2', __('Import / Export CSV')));
    }

    public function view()
    {
        if (isset($_POST['import-step-2']) && $_FILES['csv-file']['name'] != '') {
            // Import step 2:
            $this->__importStep2Page();
        } elseif (isset($_POST['import-step-3'])) {
            // Import step 3:
            $this->__importStep3Page();
        } elseif (isset($_REQUEST['export'])) {
            // Export:
            $this->__exportPage();
        } elseif (isset($_POST['ajax'])) {
            // Ajax import:
            $this->__ajaxImportRows();
        } elseif (isset($_POST['multilanguage-export'])) {
            // Export multilanguage field:
            $this->__exportMultiLanguage();
        } elseif (isset($_POST['multilanguage-import']) && $_FILES['csv-file-ml']['name'] != '') {
            // Import multilanguage field:
            $this->__importMultiLanguage();
        } else {
            // Startpage:
            $this->__indexPage();
        }
    }

    private function __indexPage()
    {

        // Create the XML for the page:
        $xml = new XMLElement('data');
        $sectionsNode = new XMLElement('sections');
        $sections = SectionManager::fetch();
        foreach ($sections as $section) {
            $sectionsNode->appendChild(new XMLElement('section', General::sanitize($section->get('name')), array('id' => $section->get('id'))));
        }
        $xml->appendChild($sectionsNode);

        // Check if the multilingual-field extension is installed:
        if (in_array('multilingual_field', ExtensionManager::listInstalledHandles())) {
            $xml->setAttribute('multilanguage', 'yes');
            // Get all the multilanguage fields:
            $fields = FieldManager::fetch(null, null, 'ASC', 'sortorder', 'multilingual');
            $multilanguage = new XMLElement('multilanguage');
            foreach ($fields as $field) {
                $sectionID = $field->get('parent_section');
                $section   = FieldManager::fetch($sectionID);
                $id        = $field->get('id');
                $label     = $section->get('name').' : '.$field->get('label');
                $multilanguage->appendChild(new XMLElement('field', $label, array('id'=>$id)));
            }
            $xml->appendChild($multilanguage);
        }

        // Generate the HTML:
        $xslt = new XSLTPage();
        $xslt->setXML($xml->generate());
        $xslt->setXSL(EXTENSIONS . '/importcsv/content/index.xsl', true);

        #$this->Form->setValue($xslt->generate());
        #$this->Form->setAttribute('enctype', 'multipart/form-data');
        // Nested forms are ignored by browsers, so append it to the div
        $this->Contents->setValue($xslt->generate());
    }

    /**
     * Get the CSV object as it is stored in the database.
     * @return bool|mixed
     *  The CSV object on success, false on failure
     */
    private function __getCSV()
    {
        $cache = new Cacheable(Symphony::Database());
        $data = $cache->check('importcsv');
        if ($data != false) {
            return unserialize($data['data']);
        } else {
            return false;
        }
    }

    private function __importStep2Page()
    {
        // File validation by tiloschroeder
        try {
            $csvInfo = $this->validateUploadedCSV($_FILES['csv-file']);
            $delimiter = $csvInfo['delimiter'];
            $headers = $csvInfo['headers'];
        } catch (Exception $e) {
            return $this->pageAlert($e->getMessage()  . ' ' . __('Please <a href="%s">try again</a>.', [URL . '/symphony/extension/importcsv/']), Alert::ERROR);
        }

        // Store the CSV data in the cache table, so the CSV file will not be stored on the server
        $cache = new Cacheable(Symphony::Database());
        // Get the nodes provided by this CSV file:
        $csv = new parseCSV();
        $csv->auto($_FILES['csv-file']['tmp_name']);
        $cache->write('importcsv', serialize($csv), 60 * 60 * 24); // Store for one day

        $sectionID = $_POST['section'];

        // Generate the XML:
        $xml = new XMLElement('data', null, array('section-id' => $sectionID));

        // Get the fields of this section:
        $fieldsNode = new XMLElement('fields');
        $section = SectionManager::fetch($sectionID);
        $fields = $section->fetchFields();
        foreach ($fields as $field) {
            $fieldsNode->appendChild(new XMLElement('field', $field->get('label'), array('id' => $field->get('id'))));
        }
        $xml->appendChild($fieldsNode);

        $csvNode = new XMLElement('csv');
        foreach ($csv->titles as $key) {
            $csvNode->appendChild(new XMLElement('key', $key));
        }
        $xml->appendChild($csvNode);

        // Generate the HTML:
        $xslt = new XSLTPage();
        $xslt->setXML($xml->generate());
        $xslt->setXSL(EXTENSIONS . '/importcsv/content/step2.xsl', true);
        $this->Form->setValue($xslt->generate());
    }

    private function __addVar($name, $value)
    {
        $this->Form->appendChild(new XMLElement('var', $value, array('class' => $name)));
    }

    private function __importStep3Page()
    {
        // Store the entries:
        $sectionID = $_POST['section'];
        $uniqueAction = $_POST['unique-action'];
        $uniqueField = $_POST['unique-field'];
        $batchSize = $_POST['batch-size'];
        $countNew = 0;
        $countUpdated = 0;
        $countIgnored = 0;
        $countOverwritten = 0;
        $csv = $this->__getCSV();

        // Load the information to start the importing process:
        $this->__addVar('section-id', $sectionID);
        $this->__addVar('unique-action', $uniqueAction);
        $this->__addVar('unique-field', $uniqueField);
        $this->__addVar('batch-size', $batchSize);
        $this->__addVar('import-url', SYMPHONY_URL  . '/extension/importcsv/');

        // Output the CSV-data:
        $csvData = $csv->data;
        $csvTitles = $csv->titles;
        $this->__addVar('total-entries', count($csvData));

        // Store the associated Field-ID's:
        $i = 0;
        $ids = array();
        foreach ($csvTitles as $title) {
            $ids[] = $_POST['field-' . $i];
            $i++;
        }
        $this->__addVar('field-ids', implode(',', $ids));

        $this->addScriptToHead(URL . '/extensions/importcsv/assets/import.js');
        $this->Form->appendChild(new XMLElement('h2', __('Import in progress...')));
        $progress = new XMLElement('div', null, array('class' => 'progress'));
        $bar = new XMLElement('div', 'Import starting...', array(
            'class' => 'bar',
            'role' => 'progressbar',
            'aria-valuemin' => '0',
            'aria-valuemax' => '100',
            'aria-valuenow' => '0',
            'aria-live' => 'polite',
            'aria-atomic' => 'true'
        ));
        $progress->appendChild($bar);
        $this->Form->appendChild($progress);
        #$this->Form->appendChild(new XMLElement('div', '<div class="bar"></div>', array('class' => 'progress')));
        $this->Form->appendChild(new XMLElement('div', null, array(
            'class' => 'console',
            'role' => 'log',
            'aria-live' => 'polite',
            'aria-atomic' => 'false'
        )));
    }

    private function getDrivers()
    {
        $classes = glob(EXTENSIONS . '/importcsv/drivers/*.php');
        $drivers = array();
        foreach ($classes as $class) {
            include_once($class);
            $a = explode('_', str_replace('.php', '', basename($class)));
            $driverName = '';
            for ($i = 1; $i < count($a); $i++) {
                if ($i > 1) {
                    $driverName .= '_';
                }
                $driverName .= $a[$i];
            }
            $className = 'ImportDriver_' . $driverName;
            $drivers[$driverName] = new $className;
        }

        return $drivers;
    }

    /**
     * This function imports 10 rows of the CSV data
     * @return void
     */
    private function __ajaxImportRows()
    {
        $messageSuffix = '';
        $updated = array();
        $ignored = array();
        $failed = array();

        $csv = $this->__getCSV();
        if ($csv != false) {
            // Load the drivers:
            $drivers = $this->getDrivers();

            // Default parameters:
            $currentRow = intval($_POST['row']);
            $sectionID = (int)$_POST['section-id'];
            $uniqueAction = $_POST['unique-action'];
            $uniqueField = $_POST['unique-field'];
            $fieldIDs = explode(',', $_POST['field-ids']);
            $entryID = null;
            $batchSize = min((int)$_POST['batch-size'], 200); // upper limit = 200

            // Load the CSV data of the specific rows:
            $csvTitles = $csv->titles;
            $csvData = $csv->data;

            $start = $currentRow * $batchSize;
            $end = min(($currentRow + 1) * $batchSize, count($csvData));

            #for ($i = $currentRow * 10; $i < ($currentRow + 1) * 10; $i++) {
            for ($i = $start; $i < $end; $i++) {
                // Start by creating a new entry:
                $entry = new Entry($this);
                $entry->set('section_id', $sectionID);

                // Ignore this entry?
                $ignore = false;

                // Import this row:
                $row = $csvData[$i];
                if ($row != false && $row !== $csv->titles) {
                    $csvRowNumber = $i +1;

                    // If a unique field is used, make sure there is a field selected for this:
                    if ($uniqueField != 'no' && $fieldIDs[$uniqueField] == 0) {
                        die(__('[ERROR: No field id sent for: "' . $csvTitles[$uniqueField] . '"]'));
                    }

                    // Unique action:
                    if ($uniqueField != 'no') {
                        // Check if there is an entry with this value:
                        $field = FieldManager::fetch($fieldIDs[$uniqueField]);
                        $type = $field->get('type');
                        if (isset($drivers[$type])) {
                            $drivers[$type]->setField($field);
                            $entryID = $drivers[$type]->scanDatabase($row[$csvTitles[$uniqueField]]);
                        } else {
                            $drivers['default']->setField($field);
                            $entryID = $drivers['default']->scanDatabase($row[$csvTitles[$uniqueField]]);
                        }

                        if ($entryID != false) {
                            // Update? Ignore? Add new?
                            switch ($uniqueAction) {
                                case 'update' :
                                    {
                                        $a = EntryManager::fetch($entryID);
                                        $entry = $a[0];
                                        $updated[] = array(
                                            'row' => $csvRowNumber,
                                            'ID' => $entryID
                                        );
                                        break;
                                    }
                                case 'ignore' :
                                    {
                                        $a = EntryManager::fetch($entryID);
                                        $entry = $a[0];
                                        $ignored[] = array(
                                            'row' => $csvRowNumber,
                                            'ID' => $entryID
                                        );
                                        $ignore = true;
                                        break;
                                    }
                            }
                        }
                    }

                    if (!$ignore) {
                        $errors = array();
                        // Do the actual importing:
                        $j = 0;
                        foreach ($row as $value) {
                            // When no unique field is found, treat it like a new entry
                            // Otherwise, stop processing to safe CPU power.
                            $fieldID = intval($fieldIDs[$j]);

                            // If $fieldID = 0, then `Don't use` is selected as field. So don't use it! :-P
                            if ($fieldID != 0) {
                                $field = FieldManager::fetch($fieldID);
                                // Get the corresponding field-type:
                                $type = $field->get('type');
                                if (isset($drivers[$type])) {
                                    $drivers[$type]->setField($field);
                                    $data = $drivers[$type]->import($value, $entryID);
                                } else {
                                    $drivers['default']->setField($field);
                                    $data = $drivers['default']->import($value, $entryID);
                                }
                                // Set the data:
                                if ($data != false) {
                                    $entry->setData($fieldID, $data);
                                }
                                if ($field) {
                                    $message = '';
                                    $messageCode = null;
                                    $result = $field->checkPostFieldData($value, $message, $messageCode, $entry->get('id'));

                                    if ($result !== true && !empty($message)) {
                                        $errors[$field->get('element_name')] = $message;
                                    }
                                }
                            }
                            $j++;
                        }

                        // Field validation and error handling by tiloschroeder
                        if (!empty($errors)) {
                            $failed[] = $csvRowNumber;

                            // ToDo: output the errors, first push to log.
                            Symphony::Log()->pushToLog(
                                sprintf('ImportCSV: Error(s) in row %d: %s', $csvRowNumber, print_r($errors, true)),
                                E_ERROR,
                                true
                            );
                            continue;
                        } else {
                            // Store the entry:
                            $entry->commit();
                        }
                    }
                }
            }
        } else {
            die(__('[ERROR: CSV Data not found!]'));
        }

        // Advanced reporting by tiloschroeder
        if (count($updated) > 0) {
            $readableIds = array();
            foreach ($updated as $entry) {
                if (isset($entry['row']) && isset($entry['ID'])) {
                    $readableIds[] = "row {$entry['row']} → ID {$entry['ID']}";
                }
            }
            $messageSuffix .= ' ' . __('(<span class="status-updated">updated</span>: ') . implode(', ', $readableIds) . ')';
        }
        if (count($ignored) > 0) {
            $readableIds = array();
            foreach ($ignored as $entry) {
                if (isset($entry['row']) && isset($entry['ID'])) {
                    $readableIds[] = "row {$entry['row']} → ID {$entry['ID']}";
                }
            }
            $messageSuffix .= ' ' . __('(<span class="status-ignored">ignored</span>: ') . implode(', ', $readableIds) . ')';
        }
        if (count($failed) > 0) {
            $readableRows = array();
            foreach ($failed as $f) {
                $readableRows[] = "row $f";
            }
            $messageSuffix .= ' ' . __('(<span class="status-failed">failed</span>: ') . implode(', ', $readableRows) . ', see <a href="/symphony/system/log/" target="_blank">Symphony-Log</a>)';
        }

        die('[OK]' . $messageSuffix);
    }

    private function __exportPage()
    {
        // Load the drivers:
        $drivers = $this->getDrivers();

        // Get the fields of this section:
        $sectionID = $_REQUEST['section-export'];
        $section = SectionManager::fetch($sectionID);
        $fileName = $section->get('handle') . '_' . date('Y-m-d') . '.csv';
        $fields = $section->fetchFields();

        $headers = array();
        foreach ($fields as $field) {
            $headers[] = '"' . str_replace('"', '""', $field->get('label')) . '"';
        }

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        // Show the headers:
        echo implode(';', $headers) . "\n";

         /*
         * Enable filtering!
         * Use the same filtering as with publish indexes (ie: ?filter=[field]:value)
         */
        $filter = $filter_value = $where = $joins = NULL;
        if (isset($_REQUEST['filter'])) {

            list($field_handle , $filter_value) = explode(':' , $_REQUEST['filter'] , 2);

            $field_names = explode(',' , $field_handle);

            foreach ($field_names as $field_name) {

                $filter_value = rawurldecode($filter_value);

                $filter = Symphony::Database()->fetchVar('id' , 0 , "SELECT `f`.`id`
                                          FROM `tbl_fields` AS `f`, `tbl_sections` AS `s`
                                          WHERE `s`.`id` = `f`.`parent_section`
                                          AND f.`element_name` = '$field_name'
                                          AND `s`.`handle` = '" . $section->get('handle') . "' LIMIT 1");

                $field = FieldManager::fetch($filter);

                if ($field instanceof Field) {
                    // For deprecated reasons, call the old, typo'd function name until the switch to the
                    // properly named buildDSRetrievalSQL function.
                    $field->buildDSRetrivalSQL(array($filter_value) , $joins , $where , false);
                    $filter_value = rawurlencode($filter_value);
                }
            }

            if (!is_null($where)) {
                $where = str_replace('AND' , 'OR' , $where); // multiple fields need to be OR
                $where = trim($where);
                $where = ' AND (' . substr($where , 2 , strlen($where)) . ')'; // replace leading OR with AND
            }

        }
        /*
         * End
         */

        // Show the content:
        $total = EntryManager::fetchCount($sectionID,$where,$joins);
        for($offset = 0; $offset < $total; $offset += 100)

        {
            $entries = EntryManager::fetch(null, $sectionID, 100, $offset, $where, $joins);
            foreach ($entries as $entry) {
                $line = array();
                foreach ($fields as $field) {
                    $data = $entry->getData($field->get('id'));
                    $type = $field->get('type');
                    if (isset($drivers[$type])) {
                        $drivers[$type]->setField($field);
                        $value = $drivers[$type]->export($data, $entry->get('id'));
                    } else {
                        $drivers['default']->setField($field);
                        $value = $drivers['default']->export($data, $entry->get('id'));
                    }
                    $line[] = '"' . str_replace('"', '""', trim($value)) . '"';
                }
                echo implode(';', $line) . "\r\n";
            }
        }
        die();
    }

    private function __exportMultiLanguage()
    {
        // Get the ID of the field which values should be exported:
        $fieldID = $_REQUEST['multilanguage-field-export'];

        // Get the languages:
        $supported_language_codes = $this->__getLanguages();

        // Create the CSV Headers:
        $csv = '"entry_id"';
        foreach ($supported_language_codes as $code) {
            $csv .= ';"'.$code.'"';
        }
        $csv .= "\r\n";

        // Get the data of the field:
        $data    = Symphony::Database()->fetch('SELECT * FROM `tbl_entries_data_'.$fieldID.'`;');

        // Loop through the data:
        foreach ($data as $row) {
            $entryID = $row['entry_id'];
            $csv .= '"'.$entryID.'"';
            foreach ($supported_language_codes as $code) {
                $csv .= ';"'.str_replace('"', '""', $row['value-'.$code]).'"';
            }
            $csv .= "\r\n";
        }

        // Output the CSV:
        $field = FieldManager::fetch($fieldID);
        $section   = SectionManager::fetch($field->get('parent_section'));

        $fileName = 'export-'.strtolower($section->get('handle').'-'.$field->get('element_name')).'.csv';
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        echo $csv;
        die();
    }

    private function __getLanguages()
    {
        // Get the available languages:
        $supported_language_codes = explode(',', General::sanitize(Symphony::Configuration()->get('language_codes', 'language_redirect')));
        $supported_language_codes = array_map('trim', $supported_language_codes);
        $supported_language_codes = array_filter($supported_language_codes);

        return $supported_language_codes;
    }

    private function __importMultilanguage()
    {
        // Get the ID of the field which values should be imported:
        $fieldID = $_REQUEST['multilanguage-field-import'];

        // Get the nodes provided by this CSV file:
        $csv = new parseCSV();
        $csv->auto($_FILES['csv-file-ml']['tmp_name']);

        // Get the CSV Data:
        $csvData = $csv->data;

        // Get the languages:
        $supported_language_codes = $this->__getLanguages();

        // Iterate throught each row:
        $count = 0;
        foreach ($csvData as $row) {
            if (isset($row['entry_id'])) {
                $data = array();
                $first = true;
                // Itterate according to the languages that are available, not the ones that are defined in the CSV:
                foreach ($supported_language_codes as $code) {
                    // Check if this language code exists in the CSV data:
                    if (isset($row[$code])) {
                        // The first language in the CSV data is used as the default language:
                        if ($first) {
                            $data['handle'] = General::createHandle($row[$code]);
                            $data['value']  = General::sanitize($row[$code]);
                        }
                        // Store the value for this specific language:
                        $data['handle-'.$code]          = General::createHandle($row[$code]);
                        $data['value-'.$code]           = $row[$code];
                        $data['value_format-'.$code]    = $row[$code];
                        $data['word_count-'.$code]      = substr_count($row[$code], ' ') + 1;
                        $first = false;
                    }
                }
                // Update the data in the database:
                Symphony::Database()->update($data, 'tbl_entries_data_'.$fieldID, '`entry_id` = '.trim($row['entry_id']));
                $count++;
            }
        }

        // Show the message that the import was successfull.
        $this->Form->appendChild(new XMLElement('p', __('Import successfull: ').$count.' '.__('entries updated')));
        $p = new XMLElement('p');
        $p->appendChild(new XMLElement('a', __('Import another field'), array('href'=>'?#multi')));
        $this->Form->appendChild($p);
    }

    private function validateUploadedCSV($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(__('❌ Upload failed or no file provided.'));
        }

        // Check the MIME-Type
        $allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedTypes)) {
            throw new Exception(__('❌ Uploaded file is not recognized as a valid CSV.'));
        }

        // Read the first lines
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception(__('❌ Could not read uploaded CSV file.'));
        }

        // Optional: Skip BOM
        $firstBytes = fread($handle, 3);
        if ($firstBytes !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $line1 = fgets($handle);
        if (empty(trim($line1))) {
            throw new Exception(__('❌ CSV file appears to be empty.'));
        }

        // Delimiter
        $delimiter = (substr_count($line1, ';') > substr_count($line1, ',')) ? ';' : ',';
        $headers = str_getcsv(trim($line1), $delimiter);
        if (count($headers) < 2) {
            throw new Exception(__('❌ CSV file does not contain enough columns.'));
        }

        fclose($handle);

        return [
            'delimiter' => $delimiter,
            'headers' => $headers
        ];
    }
}
