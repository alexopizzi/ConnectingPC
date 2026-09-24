<?php
/**
 * Modulo dei recapiti con visibilità per singolo recapito (D-013). Le righe vuote vengono ignorate.
 *
 * @var App\Core\View $this
 * @var string $action
 * @var list<array{kind: string, value: string, visibility: string}> $contacts
 */
$rows = [...$contacts, ...array_fill(0, 2, ['kind' => 'phone', 'value' => '', 'visibility' => 'public'])];
?>
<form class="form form--wide" method="post" action="<?= $this->e($action) ?>">
    <?= $this->csrfField() ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th scope="col"><?= $this->e($this->t('manage.contacts.kind')) ?></th>
                <th scope="col"><?= $this->e($this->t('manage.contacts.value')) ?></th>
                <th scope="col"><?= $this->e($this->t('manage.contacts.visibility')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $row): ?>
                <tr>
                    <td><select name="contacts[<?= $i ?>][kind]" aria-label="<?= $this->e($this->t('manage.contacts.kind')) ?>">
                        <?php foreach (App\Domain\Management\RelatedRecords::CONTACT_KINDS as $kind): ?>
                            <option value="<?= $kind ?>"<?= $row['kind'] === $kind ? ' selected' : '' ?>><?= $this->e($this->t('manage.contacts.kind.' . $kind)) ?></option>
                        <?php endforeach; ?>
                    </select></td>
                    <td><input class="ltr" type="text" name="contacts[<?= $i ?>][value]" value="<?= $this->e($row['value']) ?>" aria-label="<?= $this->e($this->t('manage.contacts.value')) ?>"></td>
                    <td><select name="contacts[<?= $i ?>][visibility]" aria-label="<?= $this->e($this->t('manage.contacts.visibility')) ?>">
                        <?php foreach (App\Domain\Management\RelatedRecords::VISIBILITIES as $visibility): ?>
                            <option value="<?= $visibility ?>"<?= $row['visibility'] === $visibility ? ' selected' : '' ?>><?= $this->e($this->t('manage.visibility.' . $visibility)) ?></option>
                        <?php endforeach; ?>
                    </select></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="field__hint"><?= $this->e($this->t('manage.contacts.hint')) ?></p>
    <div><button class="button" type="submit"><?= $this->e($this->t('manage.contacts.save')) ?></button></div>
</form>
