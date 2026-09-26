<section class="contact<?= !empty($contactAsPage) ? ' contact--page' : '' ?>" id="contact" aria-labelledby="contact-title">
  <div class="wrap contact__grid">
    <div class="contact__intro" data-reveal>
      <p class="kicker kicker--light">Contact</p>
      <h1 class="h2" id="contact-title">Parlons de votre carte</h1>
      <p class="contact__lead">Racontez-moi votre projet en quelques lignes. Je vous réponds par email, ou je vous rappelle si vous préférez.</p>
      <ul class="facts">
        <li><svg aria-hidden="true"><use href="#i-user"/></svg><?= h((string) config('owner_name')) ?>, créateur des sites</li>
        <?php if ($phone !== ''): ?>
          <li><svg aria-hidden="true"><use href="#i-phone"/></svg><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $phone)) ?>" data-track="tel" data-label="contact"><?= h($phone) ?></a></li>
        <?php endif; ?>
        <?php if ($publicEmail !== ''): ?>
          <li><svg aria-hidden="true"><use href="#i-mail"/></svg><a href="mailto:<?= h($publicEmail) ?>" data-track="mail" data-label="contact"><?= h($publicEmail) ?></a></li>
        <?php endif; ?>
        <?php if ($zone !== ''): ?>
          <li><svg aria-hidden="true"><use href="#i-pin"/></svg><?= h($zone) ?></li>
        <?php endif; ?>
      </ul>
      <div class="next">
        <p class="next__title">Et ensuite&nbsp;?</p>
        <ol>
          <li>Je lis votre message et je regarde votre carte actuelle.</li>
          <li>Je vous réponds avec mes questions, ou directement avec un devis.</li>
          <li>Si le devis vous convient, on fixe ensemble la date de mise en ligne.</li>
        </ol>
        <p class="next__note">Votre demande ne vous engage à rien.</p>
      </div>
    </div>

    <div class="contact__card" id="formulaire">
      <div class="thanks" data-thanks <?= $sent ? '' : 'hidden' ?> tabindex="-1">
        <svg class="thanks__art" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="28" fill="#34c38f" stroke="#24130d" stroke-width="3"/><path d="m20 33 8 8 16-17" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <h3 class="thanks__title">Merci, c'est bien reçu&nbsp;!</h3>
        <p>Votre demande est arrivée. Je reviens vers vous très vite, par email ou par téléphone.</p>
        <button type="button" class="pill pill--ink" data-thanks-reset>Envoyer un autre message</button>
      </div>

      <form class="form" method="post" action="<?= h(url('api/contact.php')) ?>" data-contact-form <?= $sent ? 'hidden' : '' ?>>
        <input type="hidden" name="token" value="<?= h(form_token()) ?>">
        <div class="form__hp" aria-hidden="true">
          <label for="website">Ne pas remplir ce champ</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <?php if ($formError !== ''): ?>
          <p class="form__alert" role="alert">
            <?= h(match ($formError) {
                'trop' => 'Trop de messages envoyés depuis votre connexion. Réessayez dans une heure.',
                'jeton' => 'Le formulaire a expiré. Merci de le renvoyer.',
                'serveur' => "Le message n'a pas pu être enregistré. Réessayez dans un instant.",
                default => 'Certains champs sont à corriger : nom, email et message sont obligatoires.',
            }) ?>
          </p>
        <?php endif; ?>

        <div class="form__row">
          <div class="field">
            <label for="f-name">Votre nom <span class="req" aria-hidden="true">*</span></label>
            <input id="f-name" name="name" type="text" autocomplete="name" required maxlength="100" placeholder="Camille Martin">
            <p class="field__error" id="f-name-error" hidden></p>
          </div>
          <div class="field">
            <label for="f-business">Votre établissement</label>
            <input id="f-business" name="business" type="text" autocomplete="organization" maxlength="120" placeholder="Le Petit Bistrot">
          </div>
        </div>
        <div class="form__row">
          <div class="field">
            <label for="f-email">Email <span class="req" aria-hidden="true">*</span></label>
            <input id="f-email" name="email" type="email" autocomplete="email" required maxlength="160" placeholder="vous@exemple.fr" inputmode="email">
            <p class="field__error" id="f-email-error" hidden></p>
          </div>
          <div class="field">
            <label for="f-phone">Téléphone</label>
            <input id="f-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" placeholder="06 12 34 56 78" inputmode="tel">
            <p class="field__error" id="f-phone-error" hidden></p>
          </div>
        </div>
        <div class="form__row">
          <div class="field">
            <label for="f-type">Type d'établissement</label>
            <select id="f-type" name="business_type">
              <option value="">Choisir…</option>
              <?php foreach (business_types() as $type): ?><option><?= h($type) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="f-size">Taille de la carte</label>
            <select id="f-size" name="menu_size">
              <option value="">Choisir…</option>
              <?php foreach (menu_sizes() as $size): ?><option><?= h($size) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <fieldset class="choice">
          <legend>La formule qui vous tente</legend>
          <div class="chips">
            <?php foreach ($offers as $key => $offer): ?>
              <label class="chip" style="--c1:<?= h($offer['colors'][0]) ?>">
                <input type="radio" name="offer" value="<?= h($key) ?>"<?= ($chosenOffer ?? 'indecis') === $key ? ' checked' : '' ?>><span><?= h($offer['name']) ?></span>
              </label>
            <?php endforeach; ?>
            <label class="chip"><input type="radio" name="offer" value="indecis"<?= ($chosenOffer ?? 'indecis') === 'indecis' ? ' checked' : '' ?>><span>Je ne sais pas encore</span></label>
          </div>
        </fieldset>

        <fieldset class="choice">
          <legend>Options <span class="field__hint">facultatif</span></legend>
          <div class="chips">
            <?php foreach (contact_options() as $key => $label): ?>
              <label class="chip chip--check"><input type="checkbox" name="options[]" value="<?= h($key) ?>"><span><?= h($label) ?></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="field">
          <label for="f-message">Votre projet <span class="req" aria-hidden="true">*</span></label>
          <textarea id="f-message" name="message" rows="5" required minlength="10" maxlength="5000" placeholder="Ma carte change deux fois par an, j'aimerais pouvoir modifier les prix moi-même…"></textarea>
          <div class="field__meta"><p class="field__error" id="f-message-error" hidden></p><span class="field__count" data-count>0 / 5000</span></div>
        </div>

        <label class="check">
          <input type="checkbox" name="callback" value="1">
          <span>Je préfère être rappelé·e par téléphone</span>
        </label>

        <div class="form__foot">
          <button class="pill pill--ink pill--lg" type="submit" data-submit>
            <span class="pill__label">Envoyer ma demande</span>
            <svg aria-hidden="true"><use href="#i-arrow-right"/></svg>
          </button>
          <p class="form__legal">Vos informations servent uniquement à répondre à votre demande. <a href="<?= h(url('confidentialite')) ?>">Confidentialité</a></p>
        </div>
        <p class="form__status" role="status" aria-live="polite" data-form-status></p>
      </form>
    </div>
  </div>
</section>
