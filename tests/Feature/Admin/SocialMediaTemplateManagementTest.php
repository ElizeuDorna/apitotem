<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SocialMediaTemplatesPanel;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\GaleriaNova;
use App\Models\Grupo;
use App\Models\Produto;
use App\Models\SocialMediaTemplate;
use App\Models\SocialMediaTemplateProduct;
use App\Models\SocialMediaIntegration;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SocialMediaTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_create_and_update_social_media_template_via_livewire(): void
    {
        $empresa = $this->createEmpresa('99.111.222/0001-88', 'Empresa Social', 'tok-social');
        $produtoA = $this->createProduto($empresa, '100', 'Arroz Tipo 1', '/storage-images/arroz.png', 12.90, 10.90);
        $produtoB = $this->createProduto($empresa, '200', 'Feijao Preto', '/storage-images/feijao.png', 9.50, 0);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(SocialMediaTemplatesPanel::class)
            ->set('nome', 'Campanha de Mercados')
            ->set('titulo', 'Oferta da semana')
            ->set('legenda', 'Aproveite os melhores precos.')
            ->set('layoutMode', 'mixed')
            ->set('imagePublishMode', 'product_images')
            ->set('scheduledStartAt', '2026-05-25T09:00')
            ->set('scheduledEndAt', '2026-05-30T18:00')
            ->set('instagramAutoPublish', true)
            ->set('publishToFacebook', true)
            ->set('facebookAutoPublish', true)
            ->set('productToAdd', (string) $produtoA->id)
            ->call('addProduct')
            ->assertSet('coverImageUrl', '/storage-images/arroz.png')
            ->set('productToAdd', (string) $produtoB->id)
            ->call('addProduct')
            ->set('selectedProducts.1.custom_image_url', 'https://cdn.example.com/feijao-social.png')
            ->call('save')
            ->assertSee('Template criado com sucesso.');

        $template = SocialMediaTemplate::query()->firstOrFail();

        $this->assertSame($empresa->id, $template->empresa_id);
        $this->assertSame('/storage-images/arroz.png', $template->cover_image_url);
    $this->assertSame('product_images', $template->image_publish_mode);
        $this->assertSame('scheduled', $template->instagram_publish_status);
        $this->assertTrue((bool) $template->publish_to_instagram);
        $this->assertTrue((bool) $template->publish_to_facebook);
        $this->assertSame('scheduled', $template->facebook_publish_status);
        $this->assertSame(2, $template->templateProducts()->count());

        $secondItem = SocialMediaTemplateProduct::query()
            ->where('social_media_template_id', $template->id)
            ->where('produto_id', $produtoB->id)
            ->firstOrFail();

        $this->assertSame('https://cdn.example.com/feijao-social.png', $secondItem->custom_image_url);

        Livewire::test(SocialMediaTemplatesPanel::class)
            ->call('editTemplate', $template->id)
            ->set('titulo', 'Oferta atualizada')
            ->set('selectedProducts.0.custom_title', 'Arroz Premium')
            ->call('save')
            ->assertSee('Template atualizado com sucesso.');

        $this->assertDatabaseHas('social_media_templates', [
            'id' => $template->id,
            'titulo' => 'Oferta atualizada',
        ]);

        $this->assertDatabaseHas('social_media_template_products', [
            'social_media_template_id' => $template->id,
            'produto_id' => $produtoA->id,
            'custom_title' => 'Arroz Premium',
        ]);
    }

    public function test_user_without_social_media_permission_cannot_access_social_media_page(): void
    {
        $empresa = $this->createEmpresa('44.111.222/0001-77', 'Empresa Restrita', 'tok-restrita');

        $user = User::factory()->create([
            'cpf' => '12312312312',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_PRODUTOS],
        ]);

        $response = $this->actingAs($user)->get(route('admin.social-media.index'));

        $response->assertForbidden();
    }

    public function test_user_with_meta_social_media_permission_can_access_social_media_page(): void
    {
        $empresa = $this->createEmpresa('44.111.222/0001-70', 'Empresa Meta Permissao', 'tok-meta-perm');

        $user = User::factory()->create([
            'cpf' => '12312312370',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_REDE_SOCIAL_META],
        ]);

        $response = $this->actingAs($user)->get(route('admin.social-media.index'));

        $response
            ->assertOk()
            ->assertSee('Instagram e Facebook')
            ->assertDontSee('href="'.route('admin.social-media.whatsapp.index').'"', false);
    }

    public function test_user_with_only_whatsapp_permission_cannot_access_meta_social_media_page(): void
    {
        $empresa = $this->createEmpresa('44.111.222/0001-71', 'Empresa WhatsApp Restrita', 'tok-wpp-restrita');

        $user = User::factory()->create([
            'cpf' => '12312312371',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_REDE_SOCIAL_WHATSAPP],
        ]);

        $response = $this->actingAs($user)->get(route('admin.social-media.index'));

        $response->assertForbidden();
    }

    public function test_social_media_page_displays_gallery_picker_for_cover_image(): void
    {
        $empresa = $this->createEmpresa('44.111.222/0001-78', 'Empresa Galeria Social', 'tok-social-galeria');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        $galleryUrl = route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_social_media' => 1]);

        $this->get(route('admin.social-media.index'))
            ->assertOk()
            ->assertSee($galleryUrl)
            ->assertSee('Buscar na galeria');
    }

    public function test_user_with_social_media_permission_can_open_gallery_picker_for_cover_image(): void
    {
        $empresa = $this->createEmpresa('44.111.222/0001-79', 'Empresa Permissao Social', 'tok-social-perm');

        GaleriaNova::query()->create([
            'code' => '12345678901234',
            'empresa_id' => $empresa->id,
            'is_public' => false,
            'name' => 'Imagem Social',
            'source_type' => 'link',
            'external_url' => 'https://cdn.example.com/social-cover.png',
            'file_path' => null,
            'image_hash' => null,
            'created_by' => null,
        ]);

        $user = User::factory()->create([
            'cpf' => '12312312313',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_REDE_SOCIAL_META],
        ]);

        $this->actingAs($user);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        $this->get(route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_social_media' => 1]))
            ->assertOk()
            ->assertSee('Selecionar para imagem principal do post');
    }

    public function test_instagram_callback_stores_connected_account_for_active_company(): void
    {
        config()->set('services.instagram_graph.app_id', 'app-id');
        config()->set('services.instagram_graph.app_secret', 'app-secret');
        config()->set('services.instagram_graph.redirect_uri', 'http://localhost/admin/rede-social/instagram/callback');
        config()->set('services.instagram_graph.version', 'v22.0');

        $empresa = $this->createEmpresa('77.999.888/0001-11', 'Empresa Instagram', 'tok-instagram');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Http::fake([
            'https://graph.facebook.com/*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-token'], 200)
                ->push(['access_token' => 'long-token', 'expires_in' => 5183944], 200),
            'https://graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [[
                    'id' => 'page-1',
                    'name' => 'Minha Pagina',
                    'access_token' => 'page-token',
                    'instagram_business_account' => [
                        'id' => 'ig-1',
                        'username' => 'mercado.oficial',
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($admin);

        $response = $this
            ->withSession([
                EmpresaContext::ADMIN_SESSION_KEY => $empresa->id,
                'social-media.instagram.state' => 'estado-instagram',
            ])
            ->get(route('admin.social-media.instagram.callback', [
                'state' => 'estado-instagram',
                'code' => 'codigo-meta',
            ]));

        $response
            ->assertRedirect(route('admin.social-media.index'))
            ->assertSessionHas('success', 'Instagram conectado com sucesso.');

        $this->assertDatabaseHas('social_media_integrations', [
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'connected',
            'instagram_username' => 'mercado.oficial',
            'instagram_business_account_id' => 'ig-1',
            'facebook_page_id' => 'page-1',
        ]);
    }

    public function test_callback_requires_explicit_selection_when_meta_returns_multiple_pages(): void
    {
        config()->set('services.instagram_graph.app_id', 'app-id');
        config()->set('services.instagram_graph.app_secret', 'app-secret');
        config()->set('services.instagram_graph.redirect_uri', 'http://localhost/admin/rede-social/instagram/callback');
        config()->set('services.instagram_graph.version', 'v22.0');

        $empresa = $this->createEmpresa('70.999.888/0001-22', 'Empresa Multi Pagina', 'tok-multi-page');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Http::fake([
            'https://graph.facebook.com/*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-token'], 200)
                ->push(['access_token' => 'long-token', 'expires_in' => 5183944], 200),
            'https://graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'page-1',
                        'name' => 'Pagina A',
                        'access_token' => 'page-token-a',
                        'instagram_business_account' => [
                            'id' => 'ig-1',
                            'username' => 'instagram.a',
                        ],
                    ],
                    [
                        'id' => 'page-2',
                        'name' => 'Pagina B',
                        'access_token' => 'page-token-b',
                        'instagram_business_account' => [
                            'id' => 'ig-2',
                            'username' => 'instagram.b',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession([
                EmpresaContext::ADMIN_SESSION_KEY => $empresa->id,
                'social-media.instagram.state' => 'estado-instagram',
            ])
            ->get(route('admin.social-media.instagram.callback', [
                'state' => 'estado-instagram',
                'code' => 'codigo-meta',
            ]));

        $response
            ->assertRedirect(route('admin.social-media.index'))
            ->assertSessionHas('success', 'Selecione a pagina do Facebook e a conta Instagram corretas para concluir a conexao.');

        $pendingSelection = session('social-media.instagram.pending-selection');

        $this->assertSame($empresa->id, $pendingSelection['empresa_id']);
        $this->assertCount(2, $pendingSelection['accounts']);
        $this->assertDatabaseMissing('social_media_integrations', [
            'empresa_id' => $empresa->id,
            'status' => 'connected',
        ]);
    }

    public function test_user_can_finish_meta_connection_with_selected_page(): void
    {
        $empresa = $this->createEmpresa('71.999.888/0001-22', 'Empresa Escolha Meta', 'tok-escolha-meta');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession([
                EmpresaContext::ADMIN_SESSION_KEY => $empresa->id,
                'social-media.instagram.pending-selection' => [
                    'empresa_id' => $empresa->id,
                    'expires_in' => 3600,
                    'accounts' => [
                        [
                            'facebook_page_id' => 'page-10',
                            'facebook_page_name' => 'Pagina Escolhida',
                            'instagram_business_account_id' => 'ig-10',
                            'instagram_user_id' => 'ig-10',
                            'instagram_username' => 'instagram.escolhido',
                            'access_token' => 'page-token-10',
                        ],
                    ],
                ],
            ])
            ->post(route('admin.social-media.instagram.complete-selection'), [
                'facebook_page_id' => 'page-10',
            ]);

        $response
            ->assertRedirect(route('admin.social-media.index'))
            ->assertSessionHas('success', 'Meta conectada com sucesso para a pagina selecionada.');

        $this->assertDatabaseHas('social_media_integrations', [
            'empresa_id' => $empresa->id,
            'status' => 'connected',
            'facebook_page_id' => 'page-10',
            'facebook_page_name' => 'Pagina Escolhida',
            'instagram_business_account_id' => 'ig-10',
            'instagram_username' => 'instagram.escolhido',
        ]);
    }

    public function test_connected_company_can_run_connection_test_from_panel(): void
    {
        config()->set('services.instagram_graph.app_secret', 'server-secret');

        $empresa = $this->createEmpresa('88.999.777/0001-11', 'Empresa Teste Conexao', 'tok-teste');

        SocialMediaIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'connected',
            'instagram_user_id' => 'ig-old',
            'instagram_username' => 'perfil_antigo',
            'instagram_business_account_id' => 'ig-200',
            'facebook_page_id' => 'page-200',
            'facebook_page_name' => 'Pagina Antiga',
            'access_token' => 'page-token',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Http::fake([
            'https://graph.facebook.com/*/ig-200*' => Http::response([
                'id' => 'ig-200',
                'username' => 'mercado.testado',
            ], 200),
            'https://graph.facebook.com/*/page-200*' => Http::response([
                'id' => 'page-200',
                'name' => 'Pagina Testada',
            ], 200),
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(SocialMediaTemplatesPanel::class)
            ->call('testIntegration')
            ->assertSee('Conexao Meta validada com sucesso para Instagram @mercado.testado e pagina Pagina Testada.');

        $this->assertDatabaseHas('social_media_integrations', [
            'empresa_id' => $empresa->id,
            'instagram_username' => 'mercado.testado',
            'facebook_page_name' => 'Pagina Testada',
        ]);
    }

    public function test_expired_token_shows_clear_message_when_testing_integration(): void
    {
        $empresa = $this->createEmpresa('55.111.888/0001-21', 'Empresa Token Expirado', 'tok-expirado');

        SocialMediaIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'connected',
            'instagram_business_account_id' => 'ig-expired',
            'facebook_page_id' => 'page-expired',
            'access_token' => 'expired-token',
            'access_token_expires_at' => now()->subMinute(),
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(SocialMediaTemplatesPanel::class)
            ->call('testIntegration')
            ->assertSee('Token da Meta expirado. Reconecte a conta desta empresa para voltar a publicar.');

        $this->assertDatabaseHas('social_media_integrations', [
            'empresa_id' => $empresa->id,
            'status' => 'expired',
            'last_error' => 'Token da Meta expirado. Reconecte a conta desta empresa para voltar a publicar.',
        ]);
    }

    public function test_expired_token_is_highlighted_in_templates_list_and_blocks_manual_publish_button(): void
    {
        $empresa = $this->createEmpresa('57.111.888/0001-21', 'Empresa Lista Expirada', 'tok-expirado-lista');

        SocialMediaIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'expired',
            'instagram_business_account_id' => 'ig-expired',
            'facebook_page_id' => 'page-expired',
            'access_token' => 'expired-token',
            'access_token_expires_at' => now()->subMinute(),
            'last_error' => 'Token da Meta expirado. Reconecte a conta desta empresa para voltar a publicar.',
        ]);

        SocialMediaTemplate::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Template Bloqueado',
            'titulo' => 'Nao publicar ainda',
            'layout_mode' => 'image',
            'publish_to_instagram' => true,
            'instagram_publish_status' => 'draft',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession([
                EmpresaContext::ADMIN_SESSION_KEY => $empresa->id,
            ])
            ->get(route('admin.social-media.index'));

        $response->assertOk();
        $response->assertSee('Publicacao bloqueada temporariamente.', false);
        $response->assertSee('Reconecte a Meta para publicar', false);
        $response->assertDontSee('wire:click="publishNow(', false);
    }

    public function test_callback_displays_clear_meta_error_message(): void
    {
        $empresa = $this->createEmpresa('66.777.888/0001-99', 'Empresa Callback', 'tok-callback');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession([
                EmpresaContext::ADMIN_SESSION_KEY => $empresa->id,
                'social-media.instagram.state' => 'estado-instagram',
            ])
            ->get(route('admin.social-media.instagram.callback', [
                'state' => 'estado-instagram',
                'error' => 'access_denied',
                'error_reason' => 'user_denied',
                'error_description' => 'Usuario cancelou a autorizacao na Meta.',
            ]));

        $response
            ->assertRedirect(route('admin.social-media.index'))
            ->assertSessionHas('error', 'Meta retornou erro na autorizacao: Usuario cancelou a autorizacao na Meta.');
    }

    public function test_template_can_publish_to_instagram_and_facebook(): void
    {
        $empresa = $this->createEmpresa('12.333.444/0001-55', 'Empresa Multi Rede', 'tok-multi');
        $produto = $this->createProduto($empresa, '300', 'Cafe Torrado', '/storage-images/cafe.png', 18.90, 15.90);

        $template = SocialMediaTemplate::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Campanha Multi Rede',
            'titulo' => 'Cafe em oferta',
            'legenda' => 'Oferta valida ate acabar o estoque.',
            'layout_mode' => 'mixed',
            'cover_image_url' => '/storage-images/cafe.png',
            'publish_to_instagram' => true,
            'publish_to_facebook' => true,
            'instagram_publish_status' => 'draft',
            'facebook_publish_status' => 'draft',
        ]);

        SocialMediaTemplateProduct::query()->create([
            'social_media_template_id' => $template->id,
            'produto_id' => $produto->id,
            'sort_order' => 1,
            'show_price' => true,
            'show_offer_price' => true,
        ]);

        SocialMediaIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'connected',
            'instagram_business_account_id' => 'ig-500',
            'instagram_username' => 'mercado.multi',
            'facebook_page_id' => 'page-500',
            'facebook_page_name' => 'Pagina Multi',
            'access_token' => 'page-token',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Http::fake([
            'https://graph.facebook.com/*/ig-500/media' => Http::response(['id' => 'ig-creation-1'], 200),
            'https://graph.facebook.com/*/ig-500/media_publish' => Http::response(['id' => 'ig-published-1'], 200),
            'https://graph.facebook.com/*/page-500/photos' => Http::response(['post_id' => 'fb-post-1'], 200),
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(SocialMediaTemplatesPanel::class)
            ->call('publishNow', $template->id)
            ->assertSee('Template publicado com sucesso em instagram e facebook.');

        $this->assertDatabaseHas('social_media_templates', [
            'id' => $template->id,
            'instagram_publish_status' => 'published',
            'facebook_publish_status' => 'published',
            'instagram_publish_id' => 'ig-published-1',
            'facebook_publish_id' => 'fb-post-1',
        ]);
    }

    public function test_template_can_publish_all_product_images_as_carousel(): void
    {
        $empresa = $this->createEmpresa('12.333.444/0001-56', 'Empresa Carrossel', 'tok-carousel');
        $produtoA = $this->createProduto($empresa, '301', 'Cafe Extra Forte', '/storage-images/cafe-extra.png', 19.90, 16.90);
        $produtoB = $this->createProduto($empresa, '302', 'Cafe Tradicional', '/storage-images/cafe-tradicional.png', 17.90, 14.90);

        $template = SocialMediaTemplate::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Campanha Carrossel',
            'titulo' => 'Cafes em oferta',
            'legenda' => 'Escolha o sabor ideal para sua loja.',
            'layout_mode' => 'mixed',
            'cover_image_url' => '/storage-images/capa-template.png',
            'image_publish_mode' => 'product_images',
            'publish_to_instagram' => true,
            'publish_to_facebook' => true,
            'instagram_publish_status' => 'draft',
            'facebook_publish_status' => 'draft',
        ]);

        SocialMediaTemplateProduct::query()->create([
            'social_media_template_id' => $template->id,
            'produto_id' => $produtoA->id,
            'sort_order' => 1,
            'custom_image_url' => 'https://cdn.example.com/cafe-extra-social.png',
            'show_price' => true,
            'show_offer_price' => true,
        ]);

        SocialMediaTemplateProduct::query()->create([
            'social_media_template_id' => $template->id,
            'produto_id' => $produtoB->id,
            'sort_order' => 2,
            'show_price' => true,
            'show_offer_price' => true,
        ]);

        SocialMediaIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'connected',
            'instagram_business_account_id' => 'ig-800',
            'instagram_username' => 'mercado.carousel',
            'facebook_page_id' => 'page-800',
            'facebook_page_name' => 'Pagina Carousel',
            'access_token' => 'page-token',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Http::fake([
            'https://graph.facebook.com/*/ig-800/media' => Http::sequence()
                ->push(['id' => 'ig-child-1'], 200)
                ->push(['id' => 'ig-child-2'], 200)
                ->push(['id' => 'ig-carousel-1'], 200),
            'https://graph.facebook.com/*/ig-800/media_publish' => Http::response(['id' => 'ig-published-carousel'], 200),
            'https://graph.facebook.com/*/page-800/photos' => Http::sequence()
                ->push(['id' => 'fb-photo-1'], 200)
                ->push(['id' => 'fb-photo-2'], 200),
            'https://graph.facebook.com/*/page-800/feed' => Http::response(['id' => 'page-800_post_1'], 200),
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(SocialMediaTemplatesPanel::class)
            ->call('publishNow', $template->id)
            ->assertSee('Template publicado com sucesso em instagram e facebook.');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/ig-800/media')
                && ($data['is_carousel_item'] ?? null) === 'true'
                && in_array(($data['image_url'] ?? null), [
                    'https://cdn.example.com/cafe-extra-social.png',
                    'http://localhost/storage-images/cafe-tradicional.png',
                ], true);
        });

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/ig-800/media')
                && ($data['media_type'] ?? null) === 'CAROUSEL'
                && ($data['children'] ?? null) === 'ig-child-1,ig-child-2';
        });

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/page-800/feed')
                && ($data['attached_media[0]'] ?? null) === '{"media_fbid":"fb-photo-1"}'
                && ($data['attached_media[1]'] ?? null) === '{"media_fbid":"fb-photo-2"}';
        });

        $this->assertDatabaseHas('social_media_templates', [
            'id' => $template->id,
            'instagram_publish_status' => 'published',
            'facebook_publish_status' => 'published',
            'instagram_publish_id' => 'ig-published-carousel',
            'facebook_publish_id' => 'page-800_post_1',
        ]);
    }

    public function test_scheduler_publishes_facebook_only_templates(): void
    {
        Carbon::setTestNow('2026-05-25 10:00:00');

        $empresa = $this->createEmpresa('23.444.555/0001-66', 'Empresa Facebook Agenda', 'tok-face-agenda');
        $produto = $this->createProduto($empresa, '400', 'Suco Integral', '/storage-images/suco.png', 11.90, 9.90);

        $template = SocialMediaTemplate::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Agenda Facebook',
            'titulo' => 'Oferta do dia',
            'legenda' => 'Publicado pelo agendamento automatico.',
            'layout_mode' => 'mixed',
            'cover_image_url' => '/storage-images/suco.png',
            'scheduled_start_at' => Carbon::now()->subMinute(),
            'scheduled_end_at' => Carbon::now()->addDay(),
            'instagram_auto_publish' => false,
            'facebook_auto_publish' => true,
            'publish_to_instagram' => false,
            'publish_to_facebook' => true,
            'instagram_publish_status' => 'disabled',
            'facebook_publish_status' => 'scheduled',
        ]);

        SocialMediaTemplateProduct::query()->create([
            'social_media_template_id' => $template->id,
            'produto_id' => $produto->id,
            'sort_order' => 1,
            'show_price' => true,
            'show_offer_price' => true,
        ]);

        SocialMediaIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
            'status' => 'connected',
            'facebook_page_id' => 'page-700',
            'facebook_page_name' => 'Pagina Agenda',
            'access_token' => 'page-token',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/page-700/photos' => Http::response(['post_id' => 'fb-post-scheduled'], 200),
        ]);

        $this->artisan('social-media:publish-scheduled')
            ->expectsOutput('Template '.$template->id.' publicado em facebook.')
            ->assertSuccessful();

        $this->assertDatabaseHas('social_media_templates', [
            'id' => $template->id,
            'facebook_publish_status' => 'published',
            'facebook_publish_id' => 'fb-post-scheduled',
        ]);

        Carbon::setTestNow();
    }

    private function createEmpresa(string $cnpjCpf, string $nome, string $token): Empresa
    {
        return Empresa::query()->create([
            'cnpj_cpf' => $cnpjCpf,
            'nome' => $nome,
            'fantasia' => $nome,
            'razaosocial' => $nome.' LTDA',
            'urlimagem' => 'empresa.png',
            'codigo' => substr(preg_replace('/\D/', '', $cnpjCpf), 0, 4),
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_pad($token, 60, 'x'),
        ]);
    }

    private function createProduto(Empresa $empresa, string $codigo, string $nome, string $image, float $preco, float $oferta): Produto
    {
        $departamento = Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Departamento '.$codigo,
        ]);

        $grupo = Grupo::query()->create([
            'empresa_id' => $empresa->id,
            'departamento_id' => $departamento->id,
            'nome' => 'Grupo '.$codigo,
        ]);

        return Produto::query()->create([
            'CODIGO' => $codigo,
            'NOME' => $nome,
            'cnpj_cpf' => preg_replace('/\D/', '', (string) $empresa->cnpj_cpf),
            'empresa_id' => $empresa->id,
            'PRECO' => $preco,
            'OFERTA' => $oferta,
            'IMG' => $image,
            'departamento_id' => $departamento->id,
            'grupo_id' => $grupo->id,
        ]);
    }
}