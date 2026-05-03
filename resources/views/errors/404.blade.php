@include('errors.layout', [
    'code' => 404,
    'title' => 'Page not found',
    'message' => 'The page you requested could not be found. It may have been moved or deleted.',
])
