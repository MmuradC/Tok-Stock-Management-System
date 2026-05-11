<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> &mdash; Tok-Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'brand':        '#8A5F41',
              'brand-dark':   '#5C3D2A',
              'brand-mid':    '#A77F60',
              'brand-light':  '#F3E4C9',
              'brand-bg':     '#FAF7F2',
              'brand-accent': '#CCD67F',
            }
          }
        }
      }
    </script>
</head>
<body class="bg-brand-bg font-sans antialiased">
<div class="flex min-h-screen">
