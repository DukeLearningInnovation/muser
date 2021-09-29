DROP VIEW IF EXISTS muser_applications;

CREATE VIEW muser_applications AS
SELECT f.id AS fid, f.uid AS application_uid, f.entity_id AS project_round_nid,
fr.field_round_target_id AS round_nid, fp.field_project_target_id AS project_nid,
nfd.uid AS project_uid,
fis.field_is_submitted_value AS is_submitted,
IFNULL(fuc.field_use_contract_value, 0) AS is_contracted,
IFNULL(csm.field_contract_signed_mentor_value, 0) AS contract_signed_mentor,
IFNULL(css.field_contract_signed_student_value, 0) AS contract_signed_student,
fs.field_status_value AS `status`
FROM flagging f
INNER JOIN node__field_round fr ON fr.entity_id = f.entity_id AND fr.bundle = 'project_round'
INNER JOIN node__field_project fp ON fp.entity_id = f.entity_id AND fp.bundle = 'project_round'
INNER JOIN flagging__field_is_submitted fis ON fis.entity_id = f.id AND fis.bundle = f.flag_id
INNER JOIN flagging__field_status fs ON fs.entity_id = f.id AND fs.bundle = f.flag_id
LEFT JOIN flagging__field_contract_signed_mentor csm ON csm.entity_id = f.id AND csm.bundle = f.flag_id
LEFT JOIN flagging__field_contract_signed_student css ON css.entity_id = f.id AND css.bundle = f.flag_id
INNER JOIN node_field_data nfd ON nfd.nid = fp.field_project_target_id
LEFT JOIN node__field_use_contract fuc ON fuc.entity_id = fp.field_project_target_id AND fuc.deleted = 0
WHERE (f.flag_id = 'favorites')
AND (f.entity_type = 'node')
AND nfd.status = 1;


DROP VIEW IF EXISTS muser_applications_aggregation;

CREATE VIEW muser_applications_aggregation AS
SELECT
  fid, project_round_nid, round_nid, project_nid, project_uid,
  IF(fid IS NOT NULL, 1, NULL) favorited,
  IF(is_submitted = 1, 1, NULL) submitted,
  IF(is_submitted = 0, 1, NULL) not_submitted,
  IF(`status` = 'pending' AND is_submitted = 1, 1, NULL) pending,
  IF(`status` = 'in_review', 1, NULL) in_review,
  IF(`status` IN ('pending', 'in_review') AND is_submitted = 1, 1, NULL) no_decision,
  IF(`status` = 'accepted', 1, NULL) accepted,
  IF(`status` = 'rejected', 1, NULL) rejected,
  IF(`status` = 'accepted' AND is_contracted = 1, 1, NULL) contract_required,
  IF(`status` = 'accepted' AND contract_signed_mentor = 1, 1, NULL) contract_signed_mentor,
  IF(`status` = 'accepted' AND contract_signed_student = 1, 1, NULL) contract_signed_student
FROM muser_applications;


DROP VIEW IF EXISTS muser_applications_counts;

CREATE VIEW muser_applications_counts AS
SELECT project_round_nid, round_nid, project_nid, project_uid,
COUNT(favorited) AS favorited,
COUNT(not_submitted) AS not_submitted, COUNT(submitted) AS submitted,
COUNT(pending) AS pending, COUNT(in_review) AS in_review, COUNT(no_decision) AS no_decision,
COUNT(accepted) AS accepted, COUNT(rejected) AS rejected,
COUNT(contract_required) AS contract_required,
COUNT(contract_signed_mentor) AS contract_signed_mentor, COUNT(contract_signed_student) AS contract_signed_student,
(COUNT(contract_required) - COUNT(contract_signed_mentor)) AS contract_required_mentor,
(COUNT(contract_required) - COUNT(contract_signed_student)) AS contract_required_students
FROM muser_applications_aggregation maa
GROUP BY project_round_nid, round_nid, project_nid, project_uid;
