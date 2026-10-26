import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b border-border/60">
                <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 md:px-6">
                    <Link href="/" className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="text-lg font-semibold tracking-tight">
                            Filmo
                        </span>
                    </Link>

                    <nav className="flex items-center gap-2">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/catalog">카탈로그</Link>
                        </Button>

                        {auth.user ? (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href="/contributor">
                                        Contributor Workspace
                                    </Link>
                                </Button>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="px-3 text-sm text-muted-foreground hover:text-foreground"
                                >
                                    로그아웃
                                </Link>
                            </>
                        ) : (
                            <>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/login">로그인</Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href="/register">회원가입</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t border-border/60">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6">
                    <p>Filmo — 배우 필모그래피 카탈로그</p>
                    <p>YouTube 웹드라마 · 스케치 코미디</p>
                </div>
            </footer>
        </div>
    );
}
