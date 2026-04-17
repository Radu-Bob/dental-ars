🧛 Dental Project: Migration & Deployment Walkthrough
1. Code Transfer (The Git Way)
On the Inspiron:

git add .

git commit -m "Refactor: Added clinic theming and rsync logic"

git push origin main

On the MSI:

git pull origin main

2. The Secret Sauce (.env)
Since .env is ignored by Git, use our surgical laser from the MSI terminal:

Bash
rsync -av ~/dental-inspiron-path/.env ~/dental-ars/.env
Note: Ensure the CLINIC_ID (1 for Ars, 2 for Dsm) is correct for the machine's current purpose.

3. Database Transplant (Nextcloud)
Export on Inspiron: mysqldump -u root -p dental_db > dental_backup.sql

Move dental_backup.sql to your Nextcloud folder.

Import on MSI: mysql -u root -p dental_db < ~/Nextcloud/dental_backup.sql

4. The "Doctor's Laptop" Windows VM Test
To test the deployment environment without melting the Inspiron:

RAM: Allocate strictly 2GB (4GB only if the host has 16GB+).

Network: Set to Bridged Adapter so you can access the Laravel site via the host's IP (e.g., 192.168.1.50).

Deployment: Install XAMPP/Laragon on the VM to mirror the doctor's likely setup.

5. Quick Commands (The "Don't Forget" List)
Sync Changes: sync-dental (our rsync alias).

Clear Cache: php artisan config:clear && php artisan view:clear

Check Theme: Inspect <body> for clinic-1 or clinic-2.

========><=========

Dtdc2


#	Name	Type
1	patient_id 	int 
2	name 	text 
3	acc_no 	text 
4	opened 	date 
5	closed 	date 
6	location 	text 
7	pobox 	int 
8	town 	text 
9	tel 	text 
10	active 	tinyint(1) 
11	email 	text 
12	occupation 	mediumtext 
13	remarks 	longtext 
14	image 	longtext 
15	p_reserved1 	date 
16	p_reserved2 	longtext 
17	gender 	char(1) 


Tanya


#	Name	Type
1	patient_id 	int 
2	name 	text 
3	acc_no 	varchar(50) 
4	opened 	text 
5	closed 	date 
6	location 	text 
7	pobox 	text 
8	town 	text 
9	tel 	text 
10	active 	tinyint(1) 
11	email 	text 
12	occupation 	text 
13	remarks 	text 
14	image 	longtext 
15	date_of_birth 	text 
16	p_reserved2 	longtext 
17	gender 	text 
18	created_at 	timestamp 
19	updated_at 	timestamp 
20	synced_at 	timestamp 
21	cloud_id 	varchar(36) 

-------------------------→←-------------------------------

[q]
Actually, as I split the main DB (792088_dtdc2) in two separate DB, for each clinic in Tanzania (792088_mint and 792088_tanya), I find myself in the position where I have to update the latest records made in the last few days. As our dev environment has seen few upgrades, the table fields are slightly different. To achieve the update I need, please give me again your help. The data from _dtdc2 has to go to _tanya (structures are included here), following these requirements:
- we copy infromation from table 'patients' to same table in _tanya; we copy just the records that have field 'active' = 1, or 'active'<> 2;
- 'patient_id' field will be incremented with 1000000 (we decided together to keep _tanya with 1000000 addition, and _mint with 2000000 addition, to avoid conflicts);
- fields 'opened', 'closed' and 'p_reserved1' are of type date, but in _tanya they are mainly text (to enable us having DD-MM-YYYY style of date in our dental data project). Many are already recorded as '0000-00-00' which gives a problem when writing in MariaDB. The ones that are '0000-00-00' should be recorded as '1970-01-01' or even an earlier year if possible (so that is obvious as not correct date when auditing and giving me the chance to verify and amend), paying attention that the field 'p_reserved1' is 'date-of-birth';
- records from 'gender' have to be forced to Upercase when recording in 'gender' in _tanya;
- for the fields 'created_at' and 'updated_at' in _tanya, they get the values of 'opened' from _dtdc2, but paying attention, if the records is the wrong date as 0000-00-00 then we put a date as 2026-01-01 as they are surely added this year;
- though I missed mentioning, let us make also the 'name' records as Upercase when writing in similar fiel in _tanya.
The structures:
792088_dtdc2 
# Name Type
1 patient_id int 
2 name text 
3 acc_no text 
4 opened date 
5 closed date 
6 location text 
7 pobox int 
8 town text 
9 tel text 
10 active tinyint(1) 
11 email text 
12 occupation mediumtext 
13 remarks longtext 
14 image longtext 
15 p_reserved1 date 
16 p_reserved2 longtext 
17 gender char(1) 


792088_tanya 
# Name Type
1 patient_id int 
2 name text 
3 acc_no varchar(50) 
4 opened text 
5 closed date 
6 location text 
7 pobox text 
8 town text 
9 tel text 
10 active tinyint(1) 
11 email text 
12 occupation text 
13 remarks text 
14 image longtext 
15 date_of_birth text 
16 p_reserved2 longtext 
17 gender text 
18 created_at timestamp 
19 updated_at timestamp 
20 synced_at timestamp 
21 cloud_id varchar(36) 

[a]
INSERT INTO `792088_tanya`.`patients` (
    `patient_id`, 
    `name`, 
    `acc_no`, 
    `opened`, 
    `closed`, 
    `location`, 
    `pobox`, 
    `town`, 
    `tel`, 
    `active`, 
    `email`, 
    `occupation`, 
    `remarks`, 
    `image`, 
    `date_of_birth`, 
    `p_reserved2`, 
    `gender`, 
    `created_at`, 
    `updated_at`
)
SELECT 
    (patient_id + 1000000),                              -- ID Increment
    UPPER(`name`),                                       -- Name to Uppercase
    `acc_no`,
    -- 'opened' as Text in YYYY-MM-DD
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '1970-01-01' 
        ELSE CAST(`opened` AS CHAR) 
    END,
    -- 'closed' as Date or NULL
    CASE 
        WHEN `closed` = '0000-00-00' OR `closed` IS NULL 
        THEN NULL 
        ELSE `closed` 
    END,
    `location`,
    `pobox`,
    `town`,
    `tel`,
    `active`,
    `email`,
    `occupation`,
    `remarks`,
    `image`,
    -- 'date_of_birth' (p_reserved1) as Text in YYYY-MM-DD
    CASE 
        WHEN `p_reserved1` = '0000-00-00' OR `p_reserved1` IS NULL 
        THEN '1900-01-01' 
        ELSE CAST(`p_reserved1` AS CHAR) 
    END,
    `p_reserved2`,
    UPPER(`gender`),                                     -- Gender to Uppercase
    -- Timestamps
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '2026-01-01 00:00:00' 
        ELSE `opened` 
    END,
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '2026-01-01 00:00:00' 
        ELSE `opened` 
    END
FROM `792088_dtdc2`.`patients`
WHERE `active` = 1 OR `active` <> 2;
----------→←--------
----------→←---------

SET SESSION sql_mode = '';

INSERT INTO `792088_tanya`.`patients` (
    `patient_id`, 
    `name`, 
    `acc_no`, 
    `opened`, 
    `closed`, 
    `location`, 
    `pobox`, 
    `town`, 
    `tel`, 
    `active`, 
    `email`, 
    `occupation`, 
    `remarks`, 
    `image`, 
    `date_of_birth`, 
    `p_reserved2`, 
    `gender`, 
    `created_at`, 
    `updated_at`
)
SELECT 
    (patient_id + 1000000),  
    UPPER(`name`), 
    `acc_no`,
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '1970-01-01' 
        ELSE CAST(`opened` AS CHAR) 
    END, 
    CASE 
        WHEN `closed` = '0000-00-00' OR `closed` IS NULL 
        THEN NULL 
        ELSE `closed` 
    END,  
    `location`,
    `pobox`,
    `town`,
    `tel`,
    `active`,
    `email`,
    `occupation`,
    `remarks`,
    `image`,
    CASE 
        WHEN `p_reserved1` = '0000-00-00' OR `p_reserved1` IS NULL 
        THEN '1900-01-01' 
        ELSE CAST(`p_reserved1` AS CHAR) 
    END,  
    `p_reserved2`,
    UPPER(`gender`),  
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '2026-01-01 00:00:00' 
        ELSE `opened` 
    END,  
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '2026-01-01 00:00:00' 
        ELSE `opened` 
    END  
FROM `792088_dtdc2`.`patients`
WHERE (`active` = 1 OR `active` <> 2) 
  AND `patient_id` > 5265;  

================== ===========
[a _mint]

SET SESSION sql_mode = '';

INSERT INTO `792088_mint`.`patients` (
    `patient_id`, 
    `name`, 
    `acc_no`, 
    `opened`, 
    `closed`, 
    `location`, 
    `pobox`, 
    `town`, 
    `tel`, 
    `active`, 
    `email`, 
    `occupation`, 
    `remarks`, 
    `image`, 
    `date_of_birth`, 
    `p_reserved2`, 
    `gender`, 
    `created_at`, 
    `updated_at`
)
SELECT 
    (patient_id + 2000000),  
    UPPER(`name`),  
    `acc_no`,
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '1970-01-01' 
        ELSE CAST(`opened` AS CHAR) 
    END,
    CASE 
        WHEN `closed` = '0000-00-00' OR `closed` IS NULL 
        THEN NULL 
        ELSE `closed` 
    END,   
    `location`,
    `pobox`,
    `town`,
    `tel`,
    `active`,
    `email`,
    `occupation`,
    `remarks`,
    `image`,
    CASE 
        WHEN `p_reserved1` = '0000-00-00' OR `p_reserved1` IS NULL 
        THEN '1900-01-01' 
        ELSE CAST(`p_reserved1` AS CHAR) 
    END,    
    `p_reserved2`,
    UPPER(`gender`),   
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '2026-01-01 00:00:00' 
        ELSE CAST(`opened` AS DATETIME) 
    END,  
    CASE 
        WHEN `opened` = '0000-00-00' OR `opened` IS NULL 
        THEN '2026-01-01 00:00:00' 
        ELSE CAST(`opened` AS DATETIME) 
    END  
FROM `792088_dtdc2`.`patients`
WHERE `active` = 2
  AND `patient_id` > 5271;

 -- Restore strictness SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

==================================><===============================
============><==========

[q]
Very good, very good indeed! Now, we need to copy the records from 'patients_clinical' from main DB (_dtdc2) to _tanya and _mint, making sure we copy only the records for patients belonging to that particular clinic ( 'active' = 1, or 'active'<>2 for _tanya, and 'active'=2 for _mint), only those records that have the 'date' > 2026-02-13 (again, to avoid duplicating what we already have), still paying attention to:

- as I already have some records from 2026-02-13, we should check that we do not duplicate (mainly cross-checking 'patient_id', 'date', 'diagnostic', 'description' -this I believe it should be sufficient, no need to check the other fields of the records);

- field 'reserved1' corresponds to 'estimate-description' in _tanya;

- double check dates not to have by any mistaken chance records as '0000-00-00' in the source.

Structures:

792088_dtdc2

# Name Type

1 patient_clinic_id int 

2 patient_id int 

3 patient_id_ver int 

4 acc_no text 

5 date date 

6 diagnostic text 

7 description longtext 

8 tooth text 

9 amount mediumtext 

10 paid mediumtext 

11 balance mediumtext 

12 reserved1 mediumtext 

13 estimate tinytext 

14 estimate_cost text 

15 estimate_paid text 

16 estimate_balance text 

17 notes longtext 

18 remarks longtext 

19 time_stamp timestamp 



792088_tanya

# Name Type

1 patient_clinic_id int 

2 patient_id int 

3 patient_id_ver int 

4 acc_no varchar(50) 

5 date date 

6 diagnostic text 

7 description longtext 

8 tooth text 

9 amount mediumtext 

10 paid mediumtext 

11 balance mediumtext 

12 estimate_description mediumtext 

13 estimate tinytext 

14 estimate_cost text 

15 estimate_paid text 

16 estimate_balance text 

17 notes longtext 

18 is_insurance_claim tinyint(1) 

19 remarks longtext 

20 time_stamp timestamp 

21 synced_at timestamp 

22 cloud_id varchar(36) 
================================><==========================
=================><=================

[a]
SET SESSION sql_mode = '';

INSERT INTO `792088_tanya`.`patients_clinical` (
    `patient_id`, `patient_id_ver`, `acc_no`, `date`, `diagnostic`, `description`, 
    `tooth`, `amount`, `paid`, `balance`, `estimate_description`, `estimate`, 
    `estimate_cost`, `estimate_paid`, `estimate_balance`, `notes`, `remarks`, `time_stamp`
)
SELECT 
    (src.patient_id + 1000000), 
    src.patient_id_ver, 
    src.acc_no,
    CASE WHEN src.date = '0000-00-00' THEN '1970-01-01' ELSE src.date END,
    src.diagnostic, 
    src.description,
    src.tooth, src.amount, src.paid, src.balance, 
    src.reserved1, 
    src.estimate, src.estimate_cost, src.estimate_paid, src.estimate_balance, 
    src.notes, src.remarks, src.time_stamp
FROM `792088_dtdc2`.`patients_clinical` AS src
JOIN `792088_dtdc2`.`patients` AS p ON src.patient_id = p.patient_id
WHERE (p.active = 1 OR p.active <> 2)
  AND src.date > '2026-02-20'
  AND NOT EXISTS (
      SELECT 1 FROM `792088_tanya`.`patients_clinical` AS dest
      WHERE dest.patient_id = (src.patient_id + 1000000)
        AND dest.date = src.date
        AND dest.diagnostic = src.diagnostic
        AND dest.description = src.description
  );

=======================><==================================
[a _mint]
SET SESSION sql_mode = '';

INSERT INTO `792088_mint`.`patients_clinical` (
    `patient_id`, `patient_id_ver`, `acc_no`, `date`, `diagnostic`, `description`, 
    `tooth`, `amount`, `paid`, `balance`, `estimate_description`, `estimate`, 
    `estimate_cost`, `estimate_paid`, `estimate_balance`, `notes`, `remarks`, `time_stamp`
)
SELECT 
    (src.patient_id + 2000000), 
    src.patient_id_ver, 
    src.acc_no,
    CASE WHEN src.date = '0000-00-00' THEN '1970-01-01' ELSE src.date END,
    src.diagnostic, 
    src.description,
    src.tooth, src.amount, src.paid, src.balance, 
    src.reserved1, 
    src.estimate, src.estimate_cost, src.estimate_paid, src.estimate_balance, 
    src.notes, src.remarks, src.time_stamp
FROM `792088_dtdc2`.`patients_clinical` AS src
JOIN `792088_dtdc2`.`patients` AS p ON src.patient_id = p.patient_id
WHERE p.active = 2
  AND src.date > '2026-02-20'
  AND NOT EXISTS (
      SELECT 1 FROM `792088_mint`.`patients_clinical` AS dest
      WHERE dest.patient_id = (src.patient_id + 2000000)
        AND dest.date = src.date
        AND dest.diagnostic = src.diagnostic
        AND dest.description = src.description
  );

SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
===================================><================


===================><==================
[q]
We get back to dental data project. The MariaDB needs some combing. In _mint, we need to sanitise the records. My dentist is typically Macedonian-stubborn and keeps on writing text in estimate_description when we have a clinical data and text in description, which messes up our more structured logic in Laravel.

Having this in mind, for the 'patients_clinical', if we have data in 'description' OR any of 'amount', 'paid', 'balance' records, even if it is text and not resembling a figure, AND data in estimate_description, but no data in 'estimate', 'estimate_cost', 'estimate_paid', 'estimate_balance', the records in 'estimate_description' gets copied in the 'remarks' field of the same row record, we make 'estimate_description' as NULL, also all the bunch of 'estimate' fields (the pure one, _cost, _paid, _balance) are getting NULL value, for all the records from 2006-02-16 inclusive.

The structure is:

792088_mint

# Name Type

1 patient_clinic_id int 

2 patient_id int 

3 patient_id_ver int 

4 acc_no varchar(50) 

5 date date 

6 diagnostic text 

7 description longtext 

8 tooth text 

9 amount mediumtext 

10 paid mediumtext 

11 balance mediumtext 

12 estimate_description mediumtext 

13 estimate tinytext 

14 estimate_cost text 

15 estimate_paid text 

16 estimate_balance text 

17 notes longtext 

18 is_insurance_claim tinyint(1) 

19 remarks longtext 

20 time_stamp timestamp 

21 synced_at timestamp 

22 cloud_id varchar(36) 


====================><================
[a]
UPDATE `792088_mint`.`patients_clinical`
SET 
    -- Move the stubborn text to remarks
    remarks = IF(remarks IS NULL OR remarks = '', estimate_description, CONCAT(remarks, ' | ', estimate_description)),
    
    -- Sanitise the estimate fields
    estimate_description = NULL,
    estimate = NULL,
    estimate_cost = NULL,
    estimate_paid = NULL,
    estimate_balance = NULL
WHERE 
    -- Date constraint (Inclusive)
    `date` >= '2006-02-20'
    
    -- Logic: Data exists in description OR any payment field
    AND (
        (description IS NOT NULL AND description != '') OR
        (amount IS NOT NULL AND amount != '') OR
        (paid IS NOT NULL AND paid != '') OR
        (balance IS NOT NULL AND balance != '')
    )
    
    -- Logic: Data exists in estimate_description
    AND (estimate_description IS NOT NULL AND estimate_description != '')
    
    -- Logic: BUT no data in the numeric estimate fields
    AND (estimate IS NULL OR estimate = '')
    AND (estimate_cost IS NULL OR estimate_cost = '')
    AND (estimate_paid IS NULL OR estimate_paid = '')
    AND (estimate_balance IS NULL OR estimate_balance = '');

=============================><==============================
===============>      <================
Update files in the folders:
rsync -av --update --exclude='.env' ~/dental-data-refactored/ ~/dental-ars/
==============>       <=============

