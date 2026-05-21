<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\ReferenceData;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$model = new Certificate($container->pdo());
$certificateTypes = (new ReferenceData($container->pdo()))->certificateTypes();

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
            <h2 class="text-4xl font-bold text-parish">Certificate Queue</h2>
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
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../../controllers/files/certificate.php?id=<?= (int) $row['id'] ?>&field=baptismal_file&mode=preview" target="_blank">Baptismal</a>
                                <?php endif; ?>
                                <?php if (!empty($row['id_file'])): ?>
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../../controllers/files/certificate.php?id=<?= (int) $row['id'] ?>&field=id_file&mode=preview" target="_blank">ID</a>
                                <?php endif; ?>
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
                                <button
                                    class="rounded-lg bg-parish px-3 py-2 text-sm font-semibold text-white"
                                    data-issue-certificate='<?= e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'
                                >Issue</button>
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
            <label><span class="mb-1 block font-bold">Sacrament / Event Date</span><input name="event_date" type="date" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Parents / Spouse / Related Names</span><input name="parent_names" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Event Place</span><input name="event_place" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Officiant / Pastor</span><input name="officiant" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Sponsors / Witnesses</span><textarea name="sponsors_witnesses" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
            <label><span class="mb-1 block font-bold">Book No.</span><input name="book_no" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Page No.</span><input name="page_no" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Remarks</span><textarea name="remarks" class="w-full rounded-lg border border-slate-200 p-3">as appears from the official parish register of this Church.</textarea></label>
        </div>
        <div class="mt-6 rounded-lg bg-green-50 p-4 text-sm font-semibold text-green-700">
            Near members should be set to Walk-in Pickup. Far members can receive an E-Certificate after their linked Peso payment is verified.
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Issue Certificate</button>
    </form>
</div>

<script>
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => {
    const modal = button.closest('.fixed');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}));
document.querySelectorAll('[data-issue-certificate]').forEach(button => button.addEventListener('click', () => {
    const row = JSON.parse(button.dataset.issueCertificate);
    document.getElementById('issue_id').value = row.id;
    document.getElementById('issue_certificate_number').value = row.certificate_number || '';
    document.getElementById('issue_certificate_type').value = row.certificate_type || 'Baptismal Certificate';
    document.getElementById('issue_recipient_name').value = row.full_name || row.member_name || '';
    document.getElementById('issue_birth_date').value = row.birth_date || '';
    document.getElementById('issue_delivery_mode').value = row.delivery_mode || row.delivery_option || (row.requester_location === 'Far from Parish' ? 'E-Certificate' : 'Walk-in Pickup');
    document.getElementById('issueSummary').textContent = `#${row.id} ${row.certificate_type} for ${row.full_name || row.member_name || 'member'}`;
    document.getElementById('issueCertificateModal').classList.remove('hidden');
    document.getElementById('issueCertificateModal').classList.add('flex');
}));
</script>
<?php app_footer(); page_end(); ?>
