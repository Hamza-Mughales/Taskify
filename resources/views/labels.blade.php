 <script>

     var label_please_wait = '{{ get_label('please_wait', 'Please wait...') }}';
     var label_please_select_records_to_delete =
     '{{ get_label('please_select_records_to_delete', 'Please select records to delete.') }}';
     var label_something_went_wrong = '{{ get_label('something_went_wrong', 'Something went wrong.') }}';
     var label_please_correct_errors = '{{ get_label('please_correct_errors', 'Please correct errors.') }}';
     var label_project_removed_from_favorite_successfully =
     '{{ get_label('project_removed_from_favorite_successfully', 'Project removed from favorite successfully.') }}';
     var label_project_marked_as_favorite_successfully =
     '{{ get_label('project_marked_as_favorite_successfully', 'Project marked as favorite successfully.') }}';
     var label_yes = '{{ get_label('yes', 'Yes') }}';
     var label_upload = '{{ get_label('upload', 'Upload') }}';
     var decimal_points = {{ intval($general_settings['decimal_points_in_currency'] ?? '2') }};
     var label_update = '{{ get_label('update', 'Update') }}';
     var label_delete = '{{ get_label('delete', 'Delete') }}';
     var label_view = '{{ get_label('view', 'View') }}';
     var label_not_assigned = '{{ get_label('not_assigned', 'Not assigned') }}';
     var label_delete_selected = '{{ get_label('delete_selected', 'Delete selected') }}';
     var label_search = '{{ get_label('search', 'Search') }}';
     var label_create = '{{ get_label('create', 'Create') }}';

     var label_min_0 = '{{ get_label('value_must_be_greater_then_0', 'Value must be greater than 0') }}';
     var label_max_100 = '{{ get_label('not_greater_then_100', 'Not greater than 100') }}';
     </script>

