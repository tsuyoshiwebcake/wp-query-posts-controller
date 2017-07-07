<?php
/*
Plugin Name: Query Posts Controller
Plugin URI: http://webcake.no003.info/
Description: カスタム投稿タイプごとに1ページに表示する最大投稿数の設定と表示の変更を可能にするプラグインです。<strong>このプラグインはベータ版です。</strong>
Author: Tsuyoshi.
Version: 0.1.0
Author URI: http://webcake.no003.info/
License: GPL
Copyright: Tsuyoshi.
*/
class QueryPostsController
{
	/** プラグイン名称 */
	const PLUGIN_NAME = 'Query Posts Controller';

	/** ページ名称 */
	const PAGE_NAME = 'Query Posts Controller';

	/** 接頭辞*/
	const PREFIX = 'qpc_';

	/**
	 *	コンストラクタ
	 */
	public function __construct() {
		// 管理者ページでのみ実行
		if( is_admin() ) {
			// プラグインメニューを追加
			add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		}

		// 表示件数を変更
		add_action( 'pre_get_posts', array( $this, 'posts_per_page' ), 9999 );
	}

	/**
	 *	表示件数を変更
	 */
	public function posts_per_page( $query ) {
		// 管理画面またはメインクエリー以外は対象外
		if ( is_admin() || ! $query->is_main_query() )
			return;

		// カスタム投稿タイプを取得する
		$args = array(
			'public' => true,
			'_builtin' => false
		);
		$post_types = get_post_types( $args );

		// 取得したカスタム投稿タイプの表示設定
		if ( count( $post_types ) != 0 ) {
			foreach ( $post_types as $post_type ) {
				// カスタム投稿タイプのアーカイブページの表示数を変更
				if ( $query->is_post_type_archive( $post_type ) && is_numeric( get_option( self::PREFIX . $post_type ) ) ) {
					$query->set( 'posts_per_page', get_option( self::PREFIX . $post_type ) );
					return;
				}

				// カスタム投稿タイプに紐づくカスタム分類を取得する
				$taxonomies = get_object_taxonomies( $post_type, 'objects' );
				if ( count( $taxonomies ) != 0 ) {
					foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
						// カスタム分類のアーカイブページの表示数を変更
						if ( $query->is_tax( $taxonomy_slug ) && is_numeric( get_option( self::PREFIX . $post_type ) ) ) {
							$query->set( 'posts_per_page', get_option( self::PREFIX . $post_type ) );
							return;
						}
					}
				}
			}
		}
	}

	/**
	 *	プラグインメニュー
	 */
	public function plugin_menu() {
		// メニューの設定にサブメニューとして追加
		add_options_page(
			// サブメニューページのタイトル
			self::PLUGIN_NAME,
			// プルダウンに表示されるメニュー名
			self::PAGE_NAME,
			// サブメニューの権限名
			'manage_options',
			// サブメニューのスラッグ
			basename( __FILE__ ),
			// サブメニューページのコールバック関数
			array( $this, 'plugin_page' )
		);
	}

	/**
	 *	プラグインページ
	 */
	public function plugin_page() {
		// 生成した一時トークンの取得
		$nonce_field = isset( $_POST[ self::PREFIX . 'of_nonce_field' ] ) ? $_POST[ self::PREFIX . 'of_nonce_field' ] : null;

		// 生成した一時トークンをチェックする
		if ( wp_verify_nonce( $nonce_field, wp_create_nonce( __FILE__ ) ) ) {
			// カスタム投稿タイプの取得
			$args = array(
				'public' => true,
				'_builtin' => false
			);
			$post_types = get_post_types( $args );

			// カスタム投稿タイプ別に表示件数を保存
			if ( count( $post_types ) != 0 ) {
				foreach ( $post_types as $post_type ) {
					update_option( self::PREFIX . $post_type, wp_unslash( $_POST[ self::PREFIX . $post_type ] ) );
				}
			}

			// 画面に更新されたことを伝えるメッセージを表示
			echo '<div class="updated"><p><strong>設定を保存しました。</strong></p></div>';
		}

		// フォームの表示
		$this->the_form();
	}

	/**
	 *	フォームの表示
	 */
	private function the_form() {
	?>
		<div class="wrap">
			<div id="icon-plugins" class="icon32"><br/></div>
			<h2><?php echo self::PLUGIN_NAME; ?></h2>
			<form method="post">
				<p>投稿タイプ別の1ページに表示する最大投稿数を設定して下さい。</p>

				<table class="form-table">
					<?php
					// カスタム投稿タイプの一覧を取得
					$args = array(
						'public' => true,
						'_builtin' => false
					);
					$post_types = get_post_types( $args );
					?>

					<?php /** 取得したカスタム投稿タイプの表示設定 */ ?>
					<?php if ( count( $post_types ) != 0 ) : ?>
						<?php foreach ( $post_types as $post_type ) : ?>
							<tr>
								<th scope="row">
									<label for="<?php echo self::PREFIX . $post_type ?>"><?php echo esc_html( get_post_type_object( $post_type )->label ); ?>の<?php _e( 'Blog pages show at most' ); ?></label>
								</th>
								<td>
									<input name="<?php echo self::PREFIX . $post_type ?>" type="number" step="1" min="1" id="<?php echo self::PREFIX . $post_type; ?>" value="<?php form_option( self::PREFIX . $post_type ); ?>" class="small-text" /> <?php _e( 'posts' ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</table>

				<?php /**フォームにhiddenフィールドとして追加するためのnonceを出力 */ ?>
				<?php wp_nonce_field( wp_create_nonce( __FILE__ ), self::PREFIX . 'of_nonce_field' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
	<?php
	}
}

// インスタンス生成
$QueryPostsController = new QueryPostsController();
