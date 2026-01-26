<?php
session_start();
$dbPrefix = getenv('DB_PREFIX') ?: '';
$servername = getenv('DOCUMENTS_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
$username = getenv('DOCUMENTS_DB_USER') ?: (getenv('DB_USER') ?: 'root');
$passwordEnv = getenv('DOCUMENTS_DB_PASS');
$passwordGlobal = getenv('DB_PASS');
$password = $passwordEnv !== false
    ? $passwordEnv
    : ($passwordGlobal !== false
        ? $passwordGlobal
        : (($username === 'root' && ($servername === 'localhost' || $servername === '127.0.0.1')) ? '' : 'makmak01'));
$dbname = getenv('DOCUMENTS_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'hr2_soliera_usm') : 'hr2_soliera_usm');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../CSS/sidebar.css">
  <style>
    .document-upload-area { border: 2px dashed #cbd5e1; transition: all 0.3s; }
    .document-upload-area:hover { border-color: #3b82f6; background-color: #f8fafc; }
    .document-card { transition: all 0.3s ease; }
    .document-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
    .stat-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../USM/navbar.php'; ?>

      <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
          <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
              <div class="flex justify-between items-center">
                <div>
                  <h1 class="text-2xl font-bold text-gray-800">Document Management</h1>
                  <p class="text-gray-600">Upload, manage, and access your documents</p>
                </div>
                <button class="btn btn-primary" onclick="showModal('documentUploadModal')">
                  <i class="fas fa-cloud-upload-alt mr-2"></i>Upload Document
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-sm">
                  <div class="card-body">
                    <h2 class="card-title">📁 My Documents</h2>
                    <div class="tabs tabs-boxed mb-4">
                      <a class="tab tab-active" onclick="filterDocuments('all')">All</a>
                      <a class="tab" onclick="filterDocuments('personal')">Personal</a>
                      <a class="tab" onclick="filterDocuments('employment')">Employment</a>
                      <a class="tab" onclick="filterDocuments('certificates')">Certificates</a>
                    </div>

                    <div class="overflow-x-auto">
                      <table class="table w-full">
                        <thead>
                          <tr>
                            <th>Document</th>
                            <th>Type</th>
                            <th>Date Uploaded</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody id="documentsTable">
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div class="stat-icon bg-red-100 text-red-600">
                                  <i class="fas fa-file-pdf"></i>
                                </div>
                                <div>
                                  <p class="font-medium">Employment Contract</p>
                                  <p class="text-xs text-gray-500">Signed copy</p>
                                </div>
                              </div>
                            </td>
                            <td>Employment</td>
                            <td>Dec 10, 2024</td>
                            <td>2.4 MB</td>
                            <td><span class="badge badge-success">Verified</span></td>
                            <td>
                              <button class="btn btn-ghost btn-xs" onclick="viewDocument('contract')">
                                <i class="fas fa-eye"></i>
                              </button>
                              <button class="btn btn-ghost btn-xs" onclick="downloadDocument('contract')">
                                <i class="fas fa-download"></i>
                              </button>
                              <button class="btn btn-ghost btn-xs" onclick="shareDocument('contract')">
                                <i class="fas fa-share"></i>
                              </button>
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div class="stat-icon bg-green-100 text-green-600">
                                  <i class="fas fa-file-word"></i>
                                </div>
                                <div>
                                  <p class="font-medium">Food Safety Certificate</p>
                                  <p class="text-xs text-gray-500">Expires: Dec 2025</p>
                                </div>
                              </div>
                            </td>
                            <td>Certificate</td>
                            <td>Nov 15, 2024</td>
                            <td>1.8 MB</td>
                            <td><span class="badge badge-success">Verified</span></td>
                            <td>
                              <button class="btn btn-ghost btn-xs" onclick="viewDocument('food_cert')">
                                <i class="fas fa-eye"></i>
                              </button>
                              <button class="btn btn-ghost btn-xs" onclick="downloadDocument('food_cert')">
                                <i class="fas fa-download"></i>
                              </button>
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <div class="stat-icon bg-blue-100 text-blue-600">
                                  <i class="fas fa-file-image"></i>
                                </div>
                                <div>
                                  <p class="font-medium">ID Photo</p>
                                  <p class="text-xs text-gray-500">Recent passport photo</p>
                                </div>
                              </div>
                            </td>
                            <td>Personal</td>
                            <td>Oct 20, 2024</td>
                            <td>850 KB</td>
                            <td><span class="badge badge-warning">Pending Review</span></td>
                            <td>
                              <button class="btn btn-ghost btn-xs" onclick="viewDocument('id_photo')">
                                <i class="fas fa-eye"></i>
                              </button>
                              <button class="btn btn-ghost btn-xs" onclick="updateDocument('id_photo')">
                                <i class="fas fa-edit"></i>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="card bg-base-100 shadow-sm mt-6">
                  <div class="card-body">
                    <h2 class="card-title">📄 Required Documents</h2>
                    <div class="mt-4 space-y-4">
                      <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                          <p class="font-medium">Documents Pending Submission</p>
                          <p class="text-sm">2 documents require your attention</p>
                        </div>
                      </div>

                      <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-base-200 rounded">
                          <div>
                            <p class="font-medium">Annual Ethics Agreement</p>
                            <p class="text-sm text-gray-600">Due: Jan 15, 2025</p>
                          </div>
                          <button class="btn btn-sm btn-warning" onclick="uploadRequiredDoc('ethics')">
                            <i class="fas fa-upload mr-2"></i>Upload
                          </button>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-base-200 rounded">
                          <div>
                            <p class="font-medium">Tax Declaration Form</p>
                            <p class="text-sm text-gray-600">Due: Feb 28, 2025</p>
                          </div>
                          <button class="btn btn-sm btn-outline" onclick="uploadRequiredDoc('tax_form')">
                            <i class="fas fa-upload mr-2"></i>Upload
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <div class="card bg-base-100 shadow-sm">
                  <div class="card-body">
                    <h2 class="card-title">📊 Document Statistics</h2>
                    <div class="mt-4 space-y-4">
                      <div class="stats stats-vertical shadow">
                        <div class="stat">
                          <div class="stat-title">Total Documents</div>
                          <div class="stat-value">24</div>
                          <div class="stat-desc">In your repository</div>
                        </div>
                        <div class="stat">
                          <div class="stat-title">Storage Used</div>
                          <div class="stat-value">48.2</div>
                          <div class="stat-desc">MB of 100 MB</div>
                        </div>
                        <div class="stat">
                          <div class="stat-title">Verified</div>
                          <div class="stat-value">22</div>
                          <div class="stat-desc">Documents approved</div>
                        </div>
                      </div>

                      <div class="divider"></div>

                      <div class="space-y-2">
                        <h3 class="font-bold">Quick Actions</h3>
                        <button class="btn btn-outline btn-block" onclick="showModal('documentRequestModal')">
                          <i class="fas fa-file-import mr-2"></i>Request Document
                        </button>
                        <button class="btn btn-outline btn-block" onclick="showModal('folderModal')">
                          <i class="fas fa-folder-plus mr-2"></i>Create Folder
                        </button>
                        <button class="btn btn-outline btn-block" onclick="bulkDownload()">
                          <i class="fas fa-download mr-2"></i>Bulk Download
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card bg-base-100 shadow-sm mt-6">
                  <div class="card-body">
                    <h2 class="card-title">🗂️ Document Folders</h2>
                    <div class="mt-4 space-y-3">
                      <div class="flex items-center justify-between p-3 bg-base-200 rounded cursor-pointer hover:bg-base-300" onclick="openFolder('employment')">
                        <div class="flex items-center gap-3">
                          <div class="stat-icon bg-blue-100 text-blue-600">
                            <i class="fas fa-briefcase"></i>
                          </div>
                          <div>
                            <p class="font-medium">Employment</p>
                            <p class="text-xs text-gray-600">8 documents</p>
                          </div>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                      </div>
                      <div class="flex items-center justify-between p-3 bg-base-200 rounded cursor-pointer hover:bg-base-300" onclick="openFolder('certificates')">
                        <div class="flex items-center gap-3">
                          <div class="stat-icon bg-green-100 text-green-600">
                            <i class="fas fa-certificate"></i>
                          </div>
                          <div>
                            <p class="font-medium">Certificates</p>
                            <p class="text-xs text-gray-600">5 documents</p>
                          </div>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                      </div>
                      <div class="flex items-center justify-between p-3 bg-base-200 rounded cursor-pointer hover:bg-base-300" onclick="openFolder('personal')">
                        <div class="flex items-center gap-3">
                          <div class="stat-icon bg-purple-100 text-purple-600">
                            <i class="fas fa-user"></i>
                          </div>
                          <div>
                            <p class="font-medium">Personal</p>
                            <p class="text-xs text-gray-600">6 documents</p>
                          </div>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </main>
        </div>
      </div>

      <!-- Document Modals -->
      <dialog id="documentUploadModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box max-w-2xl">
          <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
          </form>
          <h3 class="font-bold text-lg mb-4">📤 Upload Document</h3>
          <form class="space-y-4" id="uploadForm">
            <div class="form-control">
              <label class="label">
                <span class="label-text">Document Type</span>
              </label>
              <select class="select select-bordered" required>
                <option value="">Select document type</option>
                <option value="contract">Employment Contract</option>
                <option value="certificate">Certificate</option>
                <option value="id">ID Document</option>
                <option value="tax">Tax Document</option>
                <option value="medical">Medical Certificate</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Document Name</span>
              </label>
              <input type="text" class="input input-bordered" placeholder="Enter document name" required>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Select File</span>
              </label>
              <div class="document-upload-area rounded-lg p-8 text-center cursor-pointer" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                <p class="font-medium">Click to upload or drag and drop</p>
                <p class="text-sm text-gray-500 mt-2">PDF, JPG, PNG, DOC up to 10MB</p>
                <input type="file" id="fileInput" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="handleFileSelect(event)">
              </div>
              <div id="filePreview" class="mt-4 hidden">
                <div class="flex items-center justify-between p-3 bg-base-200 rounded">
                  <div class="flex items-center gap-3">
                    <i class="fas fa-file text-xl"></i>
                    <div>
                      <p class="font-medium" id="fileName"></p>
                      <p class="text-xs text-gray-500" id="fileSize"></p>
                    </div>
                  </div>
                  <button type="button" class="btn btn-ghost btn-sm" onclick="removeFile()">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Description (Optional)</span>
              </label>
              <textarea class="textarea textarea-bordered h-20" placeholder="Add description..."></textarea>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Folder</span>
              </label>
              <select class="select select-bordered">
                <option value="">Select folder</option>
                <option value="employment">Employment</option>
                <option value="certificates">Certificates</option>
                <option value="personal">Personal</option>
                <option value="new">Create new folder...</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label cursor-pointer">
                <span class="label-text">Requires Verification</span>
                <input type="checkbox" class="toggle toggle-primary" checked>
              </label>
            </div>

            <div class="modal-action">
              <button type="button" class="btn btn-ghost" onclick="closeModal('documentUploadModal')">Cancel</button>
              <button type="button" class="btn btn-primary" onclick="uploadDocument()">Upload Document</button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button>close</button>
        </form>
      </dialog>

      <dialog id="documentRequestModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box max-w-2xl">
          <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
          </form>
          <h3 class="font-bold text-lg mb-4">📥 Request Document</h3>
          <form class="space-y-4">
            <div class="form-control">
              <label class="label">
                <span class="label-text">Request Type</span>
              </label>
              <select class="select select-bordered" required>
                <option value="">Select request type</option>
                <option value="copy">Document Copy</option>
                <option value="verification">Verification Letter</option>
                <option value="new">New Document</option>
                <option value="update">Document Update</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Document Name</span>
              </label>
              <input type="text" class="input input-bordered" placeholder="Enter document name" required>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Purpose</span>
              </label>
              <textarea class="textarea textarea-bordered h-24" placeholder="Explain why you need this document..." required></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-control">
                <label class="label">
                  <span class="label-text">Required By</span>
                </label>
                <input type="date" class="input input-bordered" required>
              </div>
              <div class="form-control">
                <label class="label">
                  <span class="label-text">Format</span>
                </label>
                <select class="select select-bordered">
                  <option value="pdf">PDF</option>
                  <option value="hardcopy">Hard Copy</option>
                  <option value="both">Both</option>
                </select>
              </div>
            </div>

            <div class="modal-action">
              <button type="button" class="btn btn-ghost" onclick="closeModal('documentRequestModal')">Cancel</button>
              <button type="button" class="btn btn-primary" onclick="submitDocumentRequest()">Submit Request</button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button>close</button>
        </form>
      </dialog>

      <dialog id="folderModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box max-w-md">
          <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
          </form>
          <h3 class="font-bold text-lg mb-4">📁 Create New Folder</h3>
          <form class="space-y-4">
            <div class="form-control">
              <label class="label">
                <span class="label-text">Folder Name</span>
              </label>
              <input type="text" class="input input-bordered" placeholder="Enter folder name" required>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Description (Optional)</span>
              </label>
              <textarea class="textarea textarea-bordered h-20" placeholder="Add description..."></textarea>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text">Access</span>
              </label>
              <select class="select select-bordered">
                <option value="private">Private (Only Me)</option>
                <option value="hr">HR Department</option>
                <option value="manager">My Manager</option>
              </select>
            </div>

            <div class="modal-action">
              <button type="button" class="btn btn-ghost" onclick="closeModal('folderModal')">Cancel</button>
              <button type="button" class="btn btn-primary" onclick="createFolder()">Create Folder</button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button>close</button>
        </form>
      </dialog>

      <?php include 'modals.php'; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function showModal(modalId) {
      document.getElementById(modalId).showModal();
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).close();
    }
    
    let selectedFile = null;
    
    function handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        selectedFile = file;
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        document.getElementById('filePreview').classList.remove('hidden');
      }
    }
    
    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function removeFile() {
      selectedFile = null;
      document.getElementById('fileInput').value = '';
      document.getElementById('filePreview').classList.add('hidden');
    }
    
    function uploadDocument() {
      if (!selectedFile) {
        Swal.fire('Error!', 'Please select a file to upload', 'error');
        return;
      }
      
      Swal.fire({
        title: 'Upload Document?',
        text: 'Are you sure you want to upload this document?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Upload',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Uploading...',
            text: 'Please wait while we upload your document',
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false
          });
          
          // Simulate upload
          setTimeout(() => {
            Swal.fire('Success!', 'Document uploaded successfully!', 'success');
            closeModal('documentUploadModal');
            removeFile();
            // Reload to show new document
            setTimeout(() => location.reload(), 1500);
          }, 2000);
        }
      });
    }
    
    function viewDocument(docId) {
      Swal.fire({
        title: 'Document Preview',
        html: `<div class="text-center">
          <i class="fas fa-file-pdf text-6xl text-red-500 mb-4"></i>
          <p class="font-bold">${docId.replace('_', ' ').toUpperCase()}</p>
          <p class="text-sm text-gray-600 mt-2">Preview loading...</p>
        </div>`,
        showConfirmButton: true,
        confirmButtonText: 'Open Full View',
        showCancelButton: true,
        cancelButtonText: 'Close'
      }).then((result) => {
        if (result.isConfirmed) {
          window.open(`/documents/${docId}.pdf`, '_blank');
        }
      });
    }
    
    function downloadDocument(docId) {
      Swal.fire({
        title: 'Download Document?',
        text: 'Do you want to download this document?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Download',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Success!', 'Document download started...', 'success');
        }
      });
    }
    
    function shareDocument(docId) {
      Swal.fire({
        title: 'Share Document',
        input: 'email',
        inputLabel: 'Enter email address',
        inputPlaceholder: 'recipient@example.com',
        showCancelButton: true,
        confirmButtonText: 'Send',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: (email) => {
          return new Promise((resolve) => {
            setTimeout(() => {
              resolve({ email: email });
            }, 1000);
          });
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Success!', `Document shared with ${result.value.email}`, 'success');
        }
      });
    }
    
    function updateDocument(docId) {
      Swal.fire({
        title: 'Update Document',
        text: 'Upload a new version of this document',
        input: 'file',
        inputAttributes: {
          accept: '.pdf,.jpg,.jpeg,.png,.doc,.docx',
          'aria-label': 'Upload your document'
        },
        showCancelButton: true,
        confirmButtonText: 'Upload',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Success!', 'Document updated successfully!', 'success');
        }
      });
    }
    
    function uploadRequiredDoc(docType) {
      showModal('documentUploadModal');
    }
    
    function submitDocumentRequest() {
      Swal.fire({
        title: 'Submit Request?',
        text: 'Are you sure you want to submit this document request?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Success!', 'Document request submitted successfully!', 'success');
          closeModal('documentRequestModal');
        }
      });
    }
    
    function createFolder() {
      Swal.fire({
        title: 'Create Folder?',
        text: 'Are you sure you want to create this folder?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Create',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Success!', 'Folder created successfully!', 'success');
          closeModal('folderModal');
        }
      });
    }
    
    function bulkDownload() {
      Swal.fire({
        title: 'Bulk Download',
        text: 'Select documents to download',
        input: 'checkbox',
        inputValue: [],
        inputOptions: {
          'contract': 'Employment Contract',
          'food_cert': 'Food Safety Certificate',
          'id_photo': 'ID Photo',
          'tax_forms': 'Tax Forms',
          'training_certs': 'Training Certificates'
        },
        inputValidator: (result) => {
          return !result.length && 'Please select at least one document';
        },
        showCancelButton: true,
        confirmButtonText: 'Download Selected',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Downloading...',
            text: 'Preparing your documents...',
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false
          });
          
          setTimeout(() => {
            Swal.fire('Success!', 'Documents downloaded as ZIP file!', 'success');
          }, 2000);
        }
      });
    }
    
    function openFolder(folderName) {
      Swal.fire({
        title: folderName.charAt(0).toUpperCase() + folderName.slice(1) + ' Folder',
        html: `<div class="text-left">
          <p>Opening folder: <strong>${folderName}</strong></p>
          <p class="text-sm text-gray-600 mt-2">This would show all documents in this folder in a real application.</p>
        </div>`,
        icon: 'info',
        confirmButtonText: 'OK'
      });
    }
    
    function filterDocuments(type) {
      Swal.fire({
        title: 'Filter Applied',
        text: `Showing ${type} documents`,
        icon: 'info',
        timer: 1500,
        showConfirmButton: false
      });
    }
  </script>
  <script src="../JS/soliera.js"></script>
  <script src="../JS/sidebar.js"></script>
</body>
</html>