<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes Incompletos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: blue;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;

            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: blue;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            color: white;
            text-align: center;
            font-size: 22px;
        }

        .content {
            padding: 25px;
        }

        .content p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #444;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background: #e8eaf7;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid blue;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        a {
            background-color: blue;
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        a:hover {
            background-color: gray;
        }

        .footer {
            background: rgb(247, 247, 255);
            text-align: center;
            padding: 15px;
            font-size: 13px;
            color: #000000;
        }
    </style>
</head>

<body>
    <img src="https://gonzalezalonzo.net/wp-content/uploads/2024/09/Gonzalez-Alonzo-logotipo-17_copia.png"
        alt="gaa"
        style="width: 180px; filter: brightness(0) saturate(100%) invert(24%) sepia(99%) saturate(3648%) hue-rotate(203deg) brightness(97%) contrast(104%); display: block; margin: 20px auto;">

    <div class="container">
        <div class="header">
            <h1>Clientes con Archivos Incompletos</h1>
        </div>
        <div class="content">
            @if ($customers && count($customers) > 0)
                <p style="text-align:center; margin-top: 5px;">Se encontraron los siguientes
                    clientes
                    con archivos pendientes:</p>
                <ul>
                    @foreach ($customers as $customer)
                        <li>
                            <span><strong>{{ $customer->name }}</strong> —
                                {{ $customer->percentage_period }}%</span>
                            <a
                                href="http://192.168.2.249:8001/customers/{{ $customer->id }}/view">Ver</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="text-align:center; font-weight:bold; color:blue;">
                    ✅ Todos los clientes tienen sus archivos completos.
                </p>
            @endif
        </div>
        <div class="footer">
            © {{ date('Y') }} Datamid — Reporte automático de clientes
        </div>
    </div>
</body>

</html>
