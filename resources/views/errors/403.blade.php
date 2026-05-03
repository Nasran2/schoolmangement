@include('errors.layout', [
    'code' => 403,
    'title' => 'Access denied',
    'message' => 'You do not have permission to access this page or action.',
])
