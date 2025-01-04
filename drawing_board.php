<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "canvadatabase";

// Create a new connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the SAVE request (saving the drawing to the database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drawing']) && isset($_POST['full_name'])) {
    $drawing_data = $_POST['drawing'];
    $full_name = $_POST['full_name'];

    // Prepare the insert query with full name and drawing data
    if (isset($_POST['drawing_id'])) {
        // Update existing drawing
        $drawing_id = $_POST['drawing_id'];
        $stmt = $conn->prepare("UPDATE drawings SET full_name = ?, drawing_data = ? WHERE id = ?");
        $stmt->bind_param("ssi", $full_name, $drawing_data, $drawing_id);
    } else {
        // Insert new drawing
        $stmt = $conn->prepare("INSERT INTO drawings (full_name, drawing_data) VALUES (?, ?)");
        $stmt->bind_param("ss", $full_name, $drawing_data);
    }

    if ($stmt->execute()) {
        echo "Drawing saved successfully!";
    } else {
        echo "Error saving drawing: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all saved drawings from the database
$sql = "SELECT id, full_name, drawing_data FROM drawings ORDER BY created_at DESC";
$result = $conn->query($sql);
$saved_drawings = [];
while ($row = $result->fetch_assoc()) {
    $saved_drawings[] = $row;
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Canvas Drawing</title>
    <style>
        /* Existing CSS styles, no changes required here */
    </style>
</head>
<style>
    /* General Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f4f4;
        color: #333;
        padding: 30px;
        text-align: center;
    }

    h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        font-weight: normal;
        color: #333;
    }

    .canvas-container {
        position: relative;
        background-color: #fff;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
        display: inline-block;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    canvas {
        border-radius: 5px;
        border: 1px solid #ddd;
        background-color: #fafafa;
        display: block;
        margin: 0 auto;
    }

    .controls {
        margin-top: 20px;
        padding: 15px;
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        display:inline-block;
    }

    .button-container {
        margin-top: 15px;
    }

    button {
        padding: 10px 15px;
        font-size: 1rem;
        border: 1px solid #ccc;
        background-color: #7f8c8d;
        color: white;
        border-radius: 5px;
        cursor: pointer;
        margin: 5px;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #34495e;
    }

    button:active {
        background-color: #2c3e50;
    }

    button:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    /* Form Styling */
    .form-group {
        width: 100%;
        margin-top: 15px;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        color: #333;
        margin-bottom: 8px;
        font-weight: normal;
    }

    .form-group input {
        padding: 10px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fff;
        margin-bottom: 10px;
    }

    .saved-drawing img {
        max-width: 100%;
        height: auto;
        border-radius: 5px;
        margin-top: 10px;
    }

    /* Color Palette */
    .color-palette {
        display: flex;
        justify-content: center;
        margin-top: 10px;
    }

    .color-palette button {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        margin: 0 5px;
        cursor: pointer;
    }

    .color-palette button:hover {
        opacity: 0.8;
    }

    .color-palette button:active {
        transform: scale(1.1);
    }

    /* Saved Drawings Display */
    .saved-list {
        margin-top: 30px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
        justify-items: center;
        padding: 0 10px;
    }

    .saved-list .saved-item {
        text-align: center;
        padding: 15px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .saved-list .saved-item:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    .saved-list .saved-item p {
        font-size: 1rem;
        color: #555;
        margin-bottom: 10px;
    }

    .saved-list .saved-item img {
        cursor: pointer;
        border-radius: 5px;
    }

</style>
<body>
    <h2>Edit Canvas Drawing</h2>
    <div class="canvas-container">
        <canvas id="drawingCanvas" width="500" height="500"></canvas>
    </div>

    <div class="controls">
        <div class="button-container">
            <button type="button" onclick="selectTool('eraser')">Eraser</button>
            <button type="button" onclick="selectTool('pen')">Pen</button>
            <button type="button" onclick="undo()">Undo</button>
            <button type="button" onclick="redo()">Redo</button>
        </div>

        <!-- Color Palette -->
        <div class="color-palette">
            <button style="background-color: #000;" onclick="changeColor('#000')"></button>
            <button style="background-color: #ff0000;" onclick="changeColor('#ff0000')"></button>
            <button style="background-color: #00ff00;" onclick="changeColor('#00ff00')"></button>
            <button style="background-color: #0000ff;" onclick="changeColor('#0000ff')"></button>
            <button style="background-color: #ffff00;" onclick="changeColor('#ffff00')"></button>
            <button style="background-color: #ff00ff;" onclick="changeColor('#ff00ff')"></button>
            <button style="background-color: #00ffff;" onclick="changeColor('#00ffff')"></button>
            <button style="background-color: #ffffff;" onclick="changeColor('#ffffff')"></button>
        </div>

        <form id="saveForm" action="" method="POST" class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" name="full_name" id="full_name" required><br><br>
            <input type="hidden" name="drawing" id="drawingData">
            <input type="hidden" name="drawing_id" id="drawing_id">
            <div class="button-container">
                <button type="button" onclick="saveDrawing()">Save Drawing</button>
                <button type="button" onclick="clearCanvas()">Clear Canvas</button>
            </div>
        </form>
    </div>

    <h3>Saved Drawings:</h3>
    <div class="saved-list">
        <?php foreach ($saved_drawings as $drawing): ?>
            <div class="saved-item">
                <p><strong>Saved by:</strong> <?php echo htmlspecialchars($drawing['full_name']); ?></p>
                <img src="<?php echo $drawing['drawing_data']; ?>" alt="Saved Drawing" onclick="editDrawing('<?php echo $drawing['drawing_data']; ?>', <?php echo $drawing['id']; ?>)">
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        const canvas = document.getElementById('drawingCanvas');
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let currentColor = '#000000'; // Default color
        let currentTool = 'pen'; // Default tool set to 'pen'
        let drawingHistory = []; // History for undo
        let redoHistory = []; // History for redo

        // Set the initial canvas state
        saveCanvasState();

        // Event listeners for canvas drawing
        canvas.addEventListener('mousedown', () => drawing = true);
        canvas.addEventListener('mouseup', () => {
            drawing = false;
            ctx.beginPath();
            saveCanvasState();
        });
        canvas.addEventListener('mousemove', draw);

        // Function to draw on canvas
        function draw(event) {
            if (!drawing) return;

            // Get the canvas's position relative to the page
            const rect = canvas.getBoundingClientRect();

            // Adjust mouse position to canvas position
            const offsetX = event.clientX - rect.left;
            const offsetY = event.clientY - rect.top;

            // Set eraser size
            if (currentTool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.lineWidth = 10; // Eraser size
                ctx.strokeStyle = '#FFFFFF'; // White color for eraser
            } else {
                ctx.globalCompositeOperation = 'source-over';
                ctx.lineWidth = 5; // Pen size
                ctx.strokeStyle = currentColor; // Pen color
            }

            // Draw the line on the canvas
            ctx.lineTo(offsetX, offsetY);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(offsetX, offsetY);
        }

        // Select tool (eraser or pen)
        function selectTool(tool) {
            currentTool = tool;
            if (tool === 'eraser') {
                canvas.classList.remove('pen-cursor');
                canvas.classList.add('eraser-cursor');
            } else {
                canvas.classList.remove('eraser-cursor');
                canvas.classList.add('pen-cursor');
            }
        }

        // Function to change pen color
        function changeColor(color) {
            currentColor = color;
        }

        // Function to undo the drawing action
        function undo() {
            if (drawingHistory.length > 0) {
                redoHistory.push(drawingHistory.pop());
                redrawCanvas();
            }
        }

        // Function to redo the drawing action
        function redo() {
            if (redoHistory.length > 0) {
                drawingHistory.push(redoHistory.pop());
                redrawCanvas();
            }
        }

        // Redraw the canvas based on the history
        function redrawCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < drawingHistory.length; i++) {
                const drawing = drawingHistory[i];
                ctx.drawImage(drawing, 0, 0);
            }
        }

        // Save the current canvas state to history
        function saveCanvasState() {
            const canvasImage = canvas.toDataURL();
            drawingHistory.push(canvasImage);
        }

        // Save drawing
        function saveDrawing() {
            const drawingData = canvas.toDataURL('image/png');
            document.getElementById('drawingData').value = drawingData;
            document.getElementById('saveForm').submit();
        }

        // Clear the canvas
        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            saveCanvasState();
        }

        // Function to load a saved drawing for editing
        function editDrawing(drawingData, drawingId) {
            // Set the drawing data to the canvas
            const image = new Image();
            image.onload = function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas before drawing
                ctx.drawImage(image, 0, 0); // Draw the saved image on the canvas
            };
            image.src = drawingData; // Load the Base64 image data

            // Set the drawing ID in the hidden form input
            document.getElementById('drawing_id').value = drawingId;
        }
    </script>
</body>
</html>
