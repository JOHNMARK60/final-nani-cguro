<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\Priest;
use App\Models\ReferenceData;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$model = new Certificate($container->pdo());
$certificateTypes = (new ReferenceData($container->pdo()))->certificateTypes();
$priestModel = new Priest($container->pdo());
$priests = $priestModel->active();
$defaultPriestName = $priests[0]['name'] ?? Priest::DEFAULT_NAME;
$priestSignatures = [];

foreach ($priests as $priest) {
    $name = (string) $priest['name'];
    $priestSignatures[$name] = (string) ($priest['signature_text'] ?: $name);
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
];

$total = $model->countQueue($filters);
$items = $model->queue($filters, $perPage, ($page - 1) * $perPage);
$totalPages = (int) max(1, ceil($total / $perPage));

page_start('Certificate Queue');
sidebar('Certificates');
app_header('Certificate Queue', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <section class="rounded-xl bg-white p-8 shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-4xl font-bold text-parish">Certificate Queue</h2>
                <button type="button" data-open="priestModal" class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">
                    <i class="bi bi-person-plus"></i>
                    Add Priest
                </button>
            </div>
            <form method="GET" class="mt-6 grid gap-4 md:grid-cols-5">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Search name, email, ref">
                <select name="status" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Status</option>
                    <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $filters['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $filters['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <input type="date" name="from" value="<?= e($filters['from']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <input type="date" name="to" value="<?= e($filters['to']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
            </form>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Request</th><th>Member</th><th>Delivery</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr class="border-t">
                            <td class="p-5 font-semibold">#<?= (int) $row['id'] ?> <?= e($row['certificate_type']) ?></td>
                            <td><?= e($row['member_name'] ?? '-') ?><div class="text-sm text-slate-500"><?= e($row['member_email'] ?? '-') ?></div></td>
                            <td>
                                <div class="font-semibold"><?= e($row['delivery_mode'] ?? $row['delivery_option'] ?? 'Walk-in Pickup') ?></div>
                                <div class="text-sm text-slate-500"><?= e($row['requester_location'] ?? 'Near Parish') ?></div>
                                <?php if (!empty($row['certificate_number'])): ?>
                                    <div class="mt-1 text-xs font-bold text-parish"><?= e($row['certificate_number']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= status_badge((string) $row['status']) ?></td>
                            <td><?= e(date('M d, Y', strtotime($row['created_at']))) ?></td>
                            <td class="flex gap-2 p-5">
                                <?php if (!empty($row['baptismal_file'])): ?>
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../../controllers/files/certificate.php?id=<?= (int) $row['id'] ?>&field=baptismal_file&mode=preview" target="_blank"><?= $row['certificate_type'] === 'Baptismal Certificate' ? 'Baptismal' : 'Supporting Doc' ?></a>
                                <?php endif; ?>
                                <?php if (!empty($row['id_file'])): ?>
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../../controllers/files/certificate.php?id=<?= (int) $row['id'] ?>&field=id_file&mode=preview" target="_blank">ID</a>
                                <?php endif; ?>
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <form method="POST" action="../../controllers/admin/certificates/approve.php" data-confirm="Approve this certificate request?" data-confirm-button="Approve">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white">Approve</button>
                                    </form>
                                    <form method="POST" action="../../controllers/admin/certificates/reject.php" data-confirm="Reject this certificate request?" data-confirm-icon="warning" data-confirm-button="Reject">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Reject</button>
                                    </form>
                                <?php elseif ($row['status'] === 'Approved' && empty($row['certificate_number'])): ?>
                                    <button
                                        class="rounded-lg bg-parish px-3 py-2 text-sm font-semibold text-white"
                                        data-issue-certificate='<?= e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'
                                    >Issue</button>
                                <?php elseif (empty($row['certificate_number'])): ?>
                                    <span class="text-sm font-semibold text-slate-400">No action</span>
                                <?php endif; ?>
                                <?php if (!empty($row['certificate_number'])): ?>
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../user/certificate_view.php?id=<?= (int) $row['id'] ?>" target="_blank">Preview</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($page, $totalPages, $filters) ?>
        </section>
    </div>
</main>

<div id="issueCertificateModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/certificates/issue.php" class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h3 class="text-3xl font-black text-parish">Issue Certificate</h3>
                <p id="issueSummary" class="text-slate-500">Prepare a walk-in certificate or e-certificate.</p>
            </div>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="issue_id">
        <div class="grid gap-4 sm:grid-cols-2">
            <label><span class="mb-1 block font-bold">Certificate Number</span><input name="certificate_number" id="issue_certificate_number" placeholder="Auto if blank" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Delivery</span><select name="delivery_mode" id="issue_delivery_mode" class="w-full rounded-lg border border-slate-200 p-3"><option>Walk-in Pickup</option><option>E-Certificate</option></select></label>
            <label>
                <span class="mb-1 block font-bold">Certificate Type</span>
                <select name="certificate_type" id="issue_certificate_type" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($certificateTypes as $type): ?>
                        <option value="<?= e($type['name']) ?>"><?= e($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Recipient Name</span><input name="recipient_name" id="issue_recipient_name" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Church Name</span><input name="church_name" value="E-Parish Church" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Parish Address</span><input name="parish_address" value="Parish Office" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Birth Date</span><input name="birth_date" id="issue_birth_date" type="date" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Sacrament / Rite Date</span><input name="event_date" type="date" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <div class="rounded-lg bg-yellow-50 p-4 text-sm font-semibold text-yellow-900 sm:col-span-2">
                Roman Catholic registry details are supplied by the parish office from the sacramental register. The member request starts the process; the church record provides the priest, sponsors/witnesses, book no., and page no.
            </div>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Parents / Spouses From Parish Register</span><input name="parent_names" placeholder="As recorded in the sacramental register" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Place of Sacrament / Rite</span><input name="event_place" placeholder="Parish church or recorded place" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label>
                <span class="mb-1 block font-bold">Priest / Parish Registrar</span>
                <input name="officiant" id="issue_officiant" value="<?= e((string) $defaultPriestName) ?>" list="priestOptions" class="w-full rounded-lg border border-slate-200 p-3">
            </label>
            <datalist id="priestOptions">
                <?php foreach ($priests as $priest): ?>
                    <option value="<?= e((string) $priest['name']) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Signature</div>
                <div id="issueSignaturePreview" class="signature-script mt-2 h-12 overflow-hidden text-4xl text-slate-900"><?= e((string) $defaultPriestName) ?></div>
            </div>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Sponsors / Witnesses From Parish Register</span><textarea name="sponsors_witnesses" placeholder="Godparents, sponsors, or witnesses as applicable" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
            <label><span class="mb-1 block font-bold">Registry Book No.</span><input name="book_no" placeholder="From parish register" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Registry Page No.</span><input name="page_no" placeholder="From parish register" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Remarks</span><textarea name="remarks" class="w-full rounded-lg border border-slate-200 p-3">as appears from the Roman Catholic parish sacramental register.</textarea></label>
        </div>
        <div class="mt-6 rounded-lg bg-yellow-50 p-4 text-sm font-semibold text-yellow-900">
            Certificate number can be auto-generated when left blank. Registry book and page numbers are entered by the parish office from the official church record.
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Issue Certificate</button>
    </form>
</div>

<div id="priestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/priests/create.php" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h3 class="text-3xl font-black text-parish">Add Priest</h3>
            </div>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <div class="space-y-4">
            <label>
                <span class="mb-1 block font-bold">Priest Name</span>
                <input name="name" required placeholder="Gabriel Romero" class="w-full rounded-lg border border-slate-200 p-3">
            </label>
            <label>
                <span class="mb-1 block font-bold">Signature Text</span>
                <input name="signature_text" placeholder="Gabriel Romero" class="w-full rounded-lg border border-slate-200 p-3">
            </label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Save Priest</button>
    </form>
</div>

<style>
.signature-script {
    font-family: "Brush Script MT", "Segoe Script", "Lucida Handwriting", cursive;
    font-weight: 400;
    letter-spacing: 0;
    transform: rotate(-2deg);
}
</style>

<script>
const defaultPriestName = <?= json_encode((string) $defaultPriestName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const priestSignatures = <?= json_encode($priestSignatures, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const priestInput = document.getElementById('issue_officiant');
const signaturePreview = document.getElementById('issueSignaturePreview');
const syncSignaturePreview = () => {
    if (!signaturePreview) {
        return;
    }

    const name = (priestInput?.value || defaultPriestName).trim() || defaultPriestName;
    signaturePreview.textContent = priestSignatures[name] || name;
};

document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
    const modal = document.getElementById(button.dataset.open);
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
}));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => {
    const modal = button.closest('.fixed');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}));
priestInput?.addEventListener('input', syncSignaturePreview);
document.querySelectorAll('[data-issue-certificate]').forEach(button => button.addEventListener('click', () => {
    const row = JSON.parse(button.dataset.issueCertificate);
    document.getElementById('issue_id').value = row.id;
    document.getElementById('issue_certificate_number').value = row.certificate_number || '';
    document.getElementById('issue_certificate_type').value = row.certificate_type || 'Baptismal Certificate';
    document.getElementById('issue_recipient_name').value = row.full_name || row.member_name || '';
    document.getElementById('issue_birth_date').value = row.birth_date || '';
    document.getElementById('issue_delivery_mode').value = row.delivery_mode || row.delivery_option || (row.requester_location === 'Far from Parish' ? 'E-Certificate' : 'Walk-in Pickup');
    if (priestInput) {
        priestInput.value = row.officiant || defaultPriestName;
    }
    syncSignaturePreview();
    document.getElementById('issueSummary').textContent = `#${row.id} ${row.certificate_type} for ${row.full_name || row.member_name || 'member'}`;
    document.getElementById('issueCertificateModal').classList.remove('hidden');
    document.getElementById('issueCertificateModal').classList.add('flex');
}));
</script>
<?php app_footer(); page_end(); ?>
