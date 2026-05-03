@include('errors.layout', [
    'code' => 500,
    'title' => 'Server error',
    'message' => 'An unexpected server error occurred. The issue has been logged for investigation.',
])
