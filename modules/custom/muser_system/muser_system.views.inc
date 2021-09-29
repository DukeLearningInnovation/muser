<?php

/**
 * @file
 * Contains views API hooks for Muser System module.
 */

/**
 * Implements hook_views_data().
 */
function muser_system_views_data() {

  $data = [];

  $data['muser_applications_counts'] = [];
  $data['muser_applications_counts']['table'] = [];
  $data['muser_applications_counts']['table']['group'] = t('Muser applications');
  $data['muser_applications_counts']['table']['provider'] = 'muser_system';

  $data['muser_applications_counts']['table']['base'] = [
    'field' => 'project_round_nid',
    'title' => t('Muser application counts'),
    'help' => t('Table showing counts of applications with statuses.'),
    'weight' => -10,
  ];

  $data['muser_applications_counts']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'nid',
      'field' => 'project_round_nid',
    ],
    'users_field_data' => [
      'left_field' => 'uid',
      'field' => 'project_uid',
    ],
  ];

  $count_fields = [
    'favorited' => [
      'title' => t('Favorited count'),
      'help' => t('Number of times favorited.'),
    ],
    'submitted' => [
      'title' => t('Submitted count'),
      'help' => t('Number of submitted applications.'),
    ],
    'not_submitted' => [
      'title' => t('Not-submitted count'),
      'help' => t('Number of applications not submitted.'),
    ],
    'pending' => [
      'title' => t('Pending count'),
      'help' => t('Number of pending applications.'),
    ],
    'in_review' => [
      'title' => t('In-review count'),
      'help' => t('Number of In-review applications.'),
    ],
    'no_decision' => [
      'title' => t('No-decision count'),
      'help' => t('Number of applications that have not been accepted or rejected.'),
    ],
    'accepted' => [
      'title' => t('Accepted count'),
      'help' => t('Number of accepted applications.'),
    ],
    'rejected' => [
      'title' => t('Rejected count'),
      'help' => t('Number of rejected applications.'),
    ],
    'contract_required' => [
      'title' => t('Contract required count'),
      'help' => t('Number of applications requiring contracts.'),
    ],
    'contract_signed_mentor' => [
      'title' => t('Contract accepted by mentor count'),
      'help' => t('Number of applications where mentor accept contracts.'),
    ],
    'contract_signed_student' => [
      'title' => t('Contract accepted by student count'),
      'help' => t('Number of applications where student accept contracts.'),
    ],
    'contract_required_mentor' => [
      'title' => t('Contract acceptance required by mentor count'),
      'help' => t('Number of applications where mentor must accept contracts.'),
    ],
    'contract_required_students' => [
      'title' => t('Contract acceptance required by students count'),
      'help' => t('Number of applications where students must accept contracts.'),
    ],
  ];

  foreach ($count_fields as $field_name => $info) {
    $data['muser_applications_counts'][$field_name] = $info + [
      'field' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ];
  } // Loop thru count fields.

  $data['muser_applications_counts']['requires_decision'] = [
    'title' => t('Applications require decision'),
    'help' => t('Shows if there are applications that have not been either accepted or rejected.'),
    'field' => [
      'title' => t('Applications require decision'),
      'help' => t('Shows if there are applications that have not been either accepted or rejected.'),
      'id' => 'requires_decision',
    ],
    'sort' => [
      'title' => t('Applications require decision'),
      'help' => t('Sorts based on if there are applications that have not been either accepted or rejected.'),
      'id' => 'requires_decision',
    ],
    'filter' => [
      'title' => t('Applications require decision'),
      'help' => t('Filters based on if there are applications that have not been either accepted or rejected.'),
      'id' => 'requires_decision',
    ],
  ];

  $data['muser_applications_counts']['requires_contracts'] = [
    'title' => t('Applications require contracts'),
    'help' => t('Shows if there are applications that have not had required contracts accepted.'),
    'field' => [
      'title' => t('Applications require contracts'),
      'help' => t('Shows if there are applications that have not had required contracts accepted.'),
      'id' => 'requires_contracts',
    ],
    'sort' => [
      'title' => t('Applications require contracts'),
      'help' => t('Sorts based on if there are applications that have not had required contracts accepted.'),
      'id' => 'requires_contracts',
    ],
    'filter' => [
      'title' => t('Applications require contracts'),
      'help' => t('Filters based on if there are applications that have not had required contracts accepted.'),
      'id' => 'requires_contracts',
    ],
  ];

  return $data;

}
