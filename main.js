const botonSubmit = document.getElementById("process-btn");
const output = document.getElementById("output-csv");
const loadingText = document.getElementById("progress-text");
const copyBtn = document.getElementById("copy-btn");

const separarDatos = () => {
    const input = document.getElementById("input-csv").value;
    const lineas = input.trim().split("\n");

    if (lineas.length < 2) {
        console.warn("No hay datos suficientes en el CSV.");
        return [];
    }

    const cabecera = lineas[0].split(";").map(col => col.trim().toLowerCase());
    const filas = lineas.slice(1);

    const objetos = filas.map(linea => {
        const valores = linea.split(";").map(val => val.trim());
        const obj = {};

        cabecera.forEach((col, i) => {
            obj[col] = valores[i] || "";
        });

        return obj;
    });

    return objetos;
};

 const enviarProducto = async(producto)=> {
    const response = await fetch('conexion.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        producto: {
            originurl: producto['originurl'],
            gsm_accessories_limit: producto['gsm_accessories_limit']
        }
    })
});

const data = await response.json();

await fetch('guardar_resultado.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        originurl: producto['originurl'],
        resultado: data.resultado || data,
        taskId: data.taskId || null,
        status: data.status || null
    })
});

    return data;
}

botonSubmit.addEventListener("click", async () => {
    const datosSeparados = separarDatos();

    output.value = "";
    copyBtn.style.display = "none";
    loadingText.textContent = "Procesando productos...";

    let resultados = [];

    for (let i = 0; i < datosSeparados.length; i++) {
        const producto = datosSeparados[i];

        try {
            console.log("Producto a enviar:", producto);

            const data = await enviarProducto(producto)

            resultados.push({
                ...producto,
                ResultadoRobot: JSON.stringify(data.resultado || data)
            });

        } catch (error) {
            console.error("Error con el producto", producto, error);
            resultados.push({
                ...producto,
                ResultadoRobot: "ERROR"
            });
        }
    }

    const headers = Object.keys(resultados[0]);
    const csvLines = [headers.join(";")];

    for (const fila of resultados) {
        const values = headers.map(h => `"${(fila[h] || "").replace(/"/g, '""')}"`);
        csvLines.push(values.join(";"));
    }

    output.value = csvLines.join("\n");
    copyBtn.style.display = "block";
    loadingText.textContent = "¡Procesamiento completado!";
});

copyBtn.addEventListener("click", () => {
    output.select();
    document.execCommand("copy");
    alert("¡CSV copiado al portapapeles!");
});
